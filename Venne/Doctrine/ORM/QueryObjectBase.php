<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Doctrine\ORM;

use Doctrine;
use DoctrineExtensions\Paginate\Paginate;
use Venne;
use Venne\Doctrine\IQueryable;
use Nette;
use Nette\Utils\Paginator;


/**
 * @author Filip Procházka
 */
abstract class QueryObjectBase implements Venne\Doctrine\IQueryObject
{

	/** @var Paginator */
	private $paginator;

	/** @var Doctrine\ORM\Query */
	private $lastQuery;



	/**
	 * @param Paginator $paginator
	 */
	public function __construct(Paginator $paginator = NULL)
	{
		$this->paginator = $paginator;
	}



	/**
	 * @return Paginator
	 */
	public function getPaginator()
	{
		return $this->paginator;
	}



	/**
	 * @param IQueryable $repository
	 * @return Doctrine\ORM\Query|Doctrine\ORM\QueryBuilder
	 */
	protected abstract function doCreateQuery(IQueryable $repository);



	/**
	 * @param IQueryable $repository
	 * @return Doctrine\ORM\Query
	 */
	protected function getQuery(IQueryable $repository)
	{
		$query = $this->doCreateQuery($repository);
		if ($query instanceof Doctrine\ORM\QueryBuilder) {
			return $this->lastQuery = $query->getQuery();

		} elseif ($query instanceof Doctrine\ORM\Query) {
			return $this->lastQuery = $query;
		}

		$class = $this->getReflection()->getMethod('doCreateQuery')->getDeclaringClass();
		throw new Nette\InvalidStateException("Method " . $class . "::doCreateQuery() must return" . " instanceof Doctrine\\ORM\\Query or instaceof Doctrine\\ORM\\QueryBuilder, " . Kdyby\Tools\Mixed::getType($query) . " given.");
	}



	/**
	 * @param IQueryable $repository
	 * @return integer
	 */
	public function count(IQueryable $repository)
	{
		return Paginate::getTotalQueryResults($this->getQuery($repository));
	}



	/**
	 * @param IQueryable $repository
	 * @return array
	 */
	public function fetch(IQueryable $repository)
	{
		$query = $this->getQuery($repository);

		if ($this->paginator) {
			$query = Paginate::getPaginateQuery($query, $this->paginator->getOffset(), $this->paginator->getLength()); // Step 2 and 3

		} else {
			$query = $query->setMaxResults(NULL)->setFirstResult(NULL);
		}

		$this->lastQuery = $query;
		return $query->getResult();
	}



	/**
	 * @param IQueryable $repository
	 * @return object
	 */
	public function fetchOne(IQueryable $repository)
	{
		$query = $this->getQuery($repository)->setFirstResult(NULL)->setMaxResults(1);

		$this->lastQuery = $query;
		return $query->getSingleResult();
	}



	/**
	 * @internal For Debugging purposes only!
	 * @return Doctrine\ORM\Query
	 */
	public function getLastQuery()
	{
		return $this->lastQuery;
	}

}