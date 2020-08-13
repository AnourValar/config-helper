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
            if (is_array($value)) {
                $html .= '<optgroup label="' . e($key) . '">';
                foreach ($value as $selectValue => $selectTitle) {
                    $html .= $this->wrapOption($selectValue, $selectTitle);
                }
                $html .= '</optgroup>';
            } else {
                $html .= $this->wrapOption($key, $value);
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
        $selected = '';
        if (in_array($key, $this->selected)) {
            $selected = ' selected="selected"';
        }

        $key = e($key);
        $value = e($value);

        return "<option value=\"$key\"$selected>$value</option>";
    }
}
