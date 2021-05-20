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
            if (!is_array($value) || (isset($value['title'], $value['attributes']) && is_array($value['attributes']))) {
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
     * @see \Iterator
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @see \Iterator
     */
    public function current()
    {
        return $this->data[$this->keys[$this->position]];
    }

    /**
     * @see \Iterator
     */
    public function key()
    {
        return $this->keys[$this->position];
    }

    /**
     * @see \Iterator
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * @see \Iterator
     */
    public function valid()
    {
        return isset($this->keys[$this->position]);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return string
     */
    protected function wrapOption($key, $value): string
    {
        if (is_string($value)) {
            $value = [
                'title' => $value,
                'attributes' => [],
            ];
        }

        $value['attributes']['value'] = $key;

        if (in_array((string) $key, $this->selected, true)) {
            $value['attributes']['selected'] = 'selected';
        }

        foreach ($value['attributes'] as $attributeName => &$attributeValue) {
            $attributeValue = sprintf('%s="%s"', $attributeName, e($attributeValue));
        }
        unset($attributeValue);

        $value['title'] = e($value['title']);

        return sprintf('<option %s>%s</option>', implode(' ', $value['attributes']), e($value['title']));
    }
}
