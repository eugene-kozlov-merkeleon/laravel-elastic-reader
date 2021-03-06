<?php


namespace Merkeleon\ElasticReader\Elastic;


class QueryBuilder
{
    protected $query = [];

    public function from(int $from)
    {
        $this->query['from'] = $from;

        return $this;
    }

    public function size(int $size)
    {
        $this->query['size'] = $size;

        return $this;
    }

    public function searchAfter($searchAfter)
    {
        if ($searchAfter)
        {
            $this->query['body']['search_after'] = $searchAfter;
        }

        return $this;
    }

    public function sort($sort)
    {
        $this->query['sort'] = [$sort, '_uid:asc'];

        return $this;
    }

    public function orderBy($orderField, $orderDirection)
    {
        return $this->sort($orderField . ':' . $orderDirection);
    }

    public function reversSort()
    {
        if (empty($this->query['sort']))
        {
            return $this;
        }

        $resultSort = [];

        foreach ($this->query['sort'] as $sort)
        {
            $sortArray = explode(':', $sort);
            if (!empty($sortArray[1] && $sortArray[1] == 'desc'))
            {
                $direction = 'asc';
            }
            else
            {
                $direction = 'desc';
            }

            $resultSort[] = $sortArray[0] . ':' . $direction;
        }

        $this->query['sort'] = $resultSort;

        return $this;
    }

    public function build()
    {
        $build = [
            'from' => (int)array_get($this->query, 'from', 0),
            'size' => (int)array_get($this->query, 'size', 50)
        ];

        if ($body = array_get($this->query, 'body'))
        {
            $build['body'] = $body;
        }

        if ($sort = array_get($this->query, 'sort'))
        {
            $build['sort'] = $sort;
        }

        if ($searchAfter = array_get($this->query, 'search_after'))
        {
            $build['search_after'] = $searchAfter;
        }

        return $build;
    }

    protected function merge(array $query, $mode = 'filter')
    {
        $this->query['body']['query']['bool'][$mode][] = $query;

        return $this;
    }

    public function where($field, $value)
    {
        $query = ['term' => [$field => $value]];

        $this->merge($query);

        return $this;
    }

    public function whereIn($field, array $values)
    {
        $query = ['terms' => [$field => $values]];

        $this->merge($query);

        return $this;
    }

    public function range($field, $start = null, $end = null)
    {
        $query = [];

        if ($start)
        {
            $query['range'][$field]['gte'] = $start;
        }

        if ($end)
        {
            $query['range'][$field]['lte'] = $end;
        }

        if ($query)
        {
            $this->merge($query);
        }

        return $this;
    }

    public function matchSubString($value, $field = null)
    {
        $words = explode(' ', $value);

        foreach ($words as $word)
        {
            $query = ['query_string' => ["query" => $this->escapeSpecialChars($word)]];

            if ($field)
            {
                $query['query_string']['default_field'] = $field;
            }

            $this->merge($query, 'must');
        }

        return $this;
    }

    public function whereOr($params)
    {
        $query = [];
        foreach ($params as $field => $value)
        {
            $query[] = ['term' => [$field => $value]];
        }

        $this->merge(['bool' => ['should' => $query]]);
    }

    protected function escapeSpecialChars($str)
    {
        // List of all special chars.
        $special_chars = ['\\', '+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':'];
        // Escape all special characters.
        foreach ($special_chars as $ch)
        {
            $str = str_replace($ch, "\\{$ch}", $str);
        }

        return $str;
    }
}
