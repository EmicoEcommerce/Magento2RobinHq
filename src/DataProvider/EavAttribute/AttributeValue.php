<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\EavAttribute;


class AttributeValue
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $value;

    /**
     * AttributeValue constructor.
     * @param string $label
     * @param $value
     */
    public function __construct(string $label, string $value)
    {
        $this->label = $label;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}