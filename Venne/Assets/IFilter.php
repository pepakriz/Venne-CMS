<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Assets;

use Venne;
use Nette\Object;
use Nette\DI\Container;
use Nette\Caching\Cache;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
interface IFilter
{


	/**
	 * Process data
	 */
	public function process($data);

}

