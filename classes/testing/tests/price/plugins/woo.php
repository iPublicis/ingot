<?php
/**
 * Handle testing for WooCommerce products
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\tests\price\plugins;


use ingot\testing\tests\flow;
use ingot\testing\tests\price\price;
use ingot\ui\util;

class woo extends price {

	/**
	 * Add variable price hooks.
	 *
	 * @since 0.1.2
	 *
	 * @access protected
	 */
	protected function variable_price_hooks() {

	}

	/**
	 * Add non-variable price hook.
	 *
	 * @since 0.1.2
	 *
	 * @access protected
	 */
	protected function non_variable_price_hooks() {

	}

	/**
	 * Use in subclass to set the variable and prices properties
	 *
	 * @since 0.1.2
	 *
	 * @access protected
	 */
	protected function set_price() {
		$this->variable = true; //?
		$this->prices = true; //?
	}

}
