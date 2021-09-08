<?php

namespace AnourValar\ConfigHelper;

class SelectOptions implements \Iterator
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $keys;

    /**
     * @var integer
     */
    private $position = 0;

    /**
     * @var array
     */
    private $selected;

    /**
     * Fill in
     *
     * @param array $data
     * @return void
     */
    public function __construct(array $data, array $selected)
    {
        $this->data = $data;
        $this->keys = array_keys($data);
        $this->selected = $selected;
    }

    /**
     * @see magic
     *
     * @return string
     */
    public function __toString()
    {
        $html = '';

        foreach ($this->data as $key => $value) {
            if (isset($value['title'], $value['attributes']) && is_scalar($value['title']) && is_array($value['attributes'])) {
                $html .= $this->wrapOption($key, $value);
            } else {
                $html .= '<optgroup label="' . e($key) . '">';
                foreach ($value as $subKey => $subValue) {
                    $html .= $this->wrapOption($subKey, $subValue);
                }
                $html .= '</optgroup>';
            }
        }

        return $html;
    }

    /**
     * Compatibility with laravelcollective/html
     *
     * @see \Iterator
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Compatibility with laravelcollective/html
     *
     * @see \Iterator
     */
    public function current()
    {
        $item = $this->data[$this->keys[$this->position]];

        // flat
        if (isset($item['title'], $item['attributes']) && is_scalar($item['title']) && is_array($item['attributes'])) {
            return $item['title'];
        }

        // optgroup
        foreach ($item as &$value) {
            $value = $value['title'];
        }
        unset($value);

        return $item;
    }

    /**
     * Compatibility with laravelcollective/html
     *
     * @see \Iterator
     */
    public function key()
    {
        return $this->keys[$this->position];
    }

    /**
     * Compatibility with laravelcollective/html
     *
     * @see \Iterator
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Compatibility with laravelcollective/html
     *
     * @see \Iterator
     */
    public function valid()
    {
        return isset($this->keys[$this->position]);
    }

    /**
     * @param mixed $value
     * @param array $option
     * @return string
     */
    protected function wrapOption($value, array $option): string
    {
        $option['attributes']['value'] = $value;

        if (in_array((string) $value, $this->selected, true)) {
            $option['attributes']['selected'] = 'selected';
        }

        foreach ($option['attributes'] as $attributeName => &$attributeValue) {
            $attributeValue = sprintf('%s="%s"', $attributeName, e($attributeValue));
        }
        unset($attributeValue);

        $option['title'] = e($option['title']);

        return sprintf('<option %s>%s</option>', implode(' ', $option['attributes']), e($option['title']));
    }
}
