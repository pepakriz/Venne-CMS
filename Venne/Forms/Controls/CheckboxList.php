<?php
/**
 * Cloned RadioList from Nette Framework distribution. Instead of radios use
 * checkboxes.
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license	http://nettephp.com/license  Nette license
 * @link	   http://addons.nettephp.com/cs/checkboxlist
 * @package	Nette\Extras
 */

/**
 * CheckboxList
 *
 * @author	David Grudl, Jan Vlcek
 * @copyright Copyright (c) 2004, 2009 David Grudl
 * @package   Nette\Extras
 */

namespace Venne\Forms\Controls;

use Nette\Utils\Html, Nette\Forms\Form, Nette\Forms\Controls\BaseControl;

class CheckboxList extends BaseControl
{

	/** @var Nette\Utils\Html  separator element template */
	protected $separator;

	/** @var Nette\Utils\Html  container element template */
	protected $container;

	/** @var array */
	protected $items = array();



	/**
	 * Form container extension method. Do not call directly.
	 *
	 * @param FormContainer $form
	 * @param string $name
	 * @param string $label
	 * @param array $items
	 * @return CheckboxList
	 */
	public static function addCheckboxList(Form $form, $name, $label, array $items = NULL)
	{
		return $form[$name] = new self($label, $items);
	}



	/**
	 * Adds addCheckboxList() method to \Nette\Forms\Form
	 */
	public static function register()
	{
		Form::extensionMethod('addCheckboxList', callback(__CLASS__, 'addCheckboxList'));
	}



	/**
	 * @param string $label
	 * @param array $items  Options from which to choose
	 */
	public function __construct($label, array $items = NULL)
	{
		parent::__construct($label);
		$this->control->type = 'checkbox';
		$this->container = Html::el();
		$this->separator = Html::el('br');
		if ($items !== NULL) $this->setItems($items);
	}



	/**
	 * Returns selected radio value. NULL means nothing have been checked.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return is_array($this->value) ? $this->value : NULL;
	}



	/**
	 * Sets options from which to choose.
	 *
	 * @param array $items
	 * @return CheckboxList  provides a fluent interface
	 */
	public function setItems(array $items)
	{
		$this->items = $items;
		return $this;
	}



	/**
	 * Returns options from which to choose.
	 *
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}



	/**
	 * Returns separator HTML element template.
	 *
	 * @return Nette\Utils\Html
	 * @return Nette\Web\Html
	 */
	public function getSeparatorPrototype()
	{
		return $this->separator;
	}



	/**
	 * Returns container HTML element template.
	 *
	 * @return Nette\Utils\Html
	 */
	public function getContainerPrototype()
	{
		return $this->container;
	}



	/**
	 * Generates control's HTML element.
	 *
	 * @param mixed $key  Specify a key if you want to render just a single checkbox
	 * @return Nette\Utils\Html
	 */
	public function getControl($key = NULL)
	{
		if ($key === NULL) {
			$container = clone $this->container;
			$separator = (string)$this->separator;

		} elseif (!isset($this->items[$key])) {
			return NULL;
		}

		$control = parent::getControl();
		$control->name .= '[]';
		$id = $control->id;
		$counter = -1;
		$values = $this->value === NULL ? NULL : (array)$this->getValue();
		$label = Html::el('label');

		foreach ($this->items as $k => $val) {
			$counter++;
			if ($key !== NULL && $key != $k) continue; // intentionally ==

			$control->id = $label->for = $id . '-' . $counter;
			$control->checked = (count($values) > 0) ? in_array($k, $values) : false;
			$control->value = $k;

			if ($val instanceof Html) {
				$label->setHtml($val);
			} else {
				$label->setText($this->translate($val));
			}

			if ($key !== NULL) {
				return (string)$control . (string)$label;
			}

			$container->add((string)$control . (string)$label . $separator);
		}

		return $container;
	}



	/**
	 * Generates label's HTML element.
	 *
	 * @return Html
	 */
	public function getLabel($caption = NULL)
	{
		$label = parent::getLabel($caption);
		$label->for = NULL;
		return $label;
	}



	/**
	 * Filled validator: has been any checkbox checked?
	 *
	 * @param IFormControl $control
	 * @return bool
	 */
	public static function validateChecked(IFormControl $control)
	{
		return $control->getValue() !== NULL;
	}

}