<?php


namespace ProAtCooking\Recipe;

use ArrayIterator;
include_once 'iMetaBox.php';

/**
 * Class MetaBoxes
 *
 * An array of iMetaBoxes (Why? For type safety!)
 *
 * @package ProAtCooking\Recipe
 */
class MetaBoxes extends ArrayIterator {
	public function __construct( iMetaBox ...$boxes ) {
		parent::__construct( $boxes );
	}

	public function current(): iMetaBox {
		return parent::current();
	}

	public function offsetGet( $offset ): iMetaBox {
		return parent::offsetGet( $offset );
	}
}