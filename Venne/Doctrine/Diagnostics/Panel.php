<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Doctrine\Diagnostics;

use Doctrine;
use Venne;
use Venne\Doctrine\SqlException;
use Nette;
use Nette\Diagnostics\Bar;
use Nette\Diagnostics\BlueScreen;
use Nette\Diagnostics\Debugger;
use Nette\Database\Connection;
use Nette\Utils\Strings;

/**
 * Debug panel for Doctrine
 *
 * @author David Grudl
 * @author Patrik Votoček
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class Panel extends Nette\Object implements Nette\Diagnostics\IBarPanel, Doctrine\DBAL\Logging\SQLLogger
{


	/** @var int logged time */
	public $totalTime = 0;

	/** @var array */
	public $queries = array();



	/***************** Doctrine\DBAL\Logging\SQLLogger ********************/


	/**
	 * @param string
	 * @param array
	 * @param array
	 */
	public function startQuery($sql, array $params = NULL, array $types = NULL)
	{
		Debugger::timer('doctrine');

		$source = NULL;
		foreach (debug_backtrace(FALSE) as $row) {
			if (isset($row['file']) && is_file($row['file']) && strpos($row['file'], NETTE_DIR . DIRECTORY_SEPARATOR) === FALSE && strpos($row['file'], "Doctrine") === FALSE && strpos($row['file'], "Repository") === FALSE) {
				$source = array($row['file'], (int)$row['line']);
				break;
			}
		}
		$this->queries[] = array($sql, $params, NULL, 0, NULL, $source);
	}



	/**
	 */
	public function stopQuery()
	{
		$keys = array_keys($this->queries);
		$key = end($keys);
		$this->queries[$key][2] = Debugger::timer('doctrine');
		$this->totalTime += $this->queries[$key][2];
	}



	/***************** Nette\Diagnostics\IBarPanel ********************/


	/**
	 * @return string
	 */
	public function getTab()
	{
		return '<span title="Doctrine 2">' . '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC" />' . count($this->queries) . ' queries' . ($this->totalTime ? ' / ' . sprintf('%0.1f', $this->totalTime * 1000) . 'ms' : '') . '</span>';
	}



	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (empty($this->queries)) {
			return "";
		}

		$s = "";
		foreach ($this->queries as $query) {
			$s .= $this->processQuery($query);
		}

		return $this->renderStyles() . '<h1>Queries: ' . count($this->queries) . ($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : '') . '</h1>
			<div class="nette-inner nette-Doctrine2Panel">
			<table>
			<tr><th>Time&nbsp;ms</th><th>SQL Statement</th><th>Params</th><th>Rows</th></tr>' . $s . '
			</table>
			</div>';
	}



	/**
	 * @return string
	 */
	protected function renderStyles()
	{
		return '<style> #nette-debug td.nette-Doctrine2Panel-sql { background: white !important}
			#nette-debug .nette-Doctrine2Panel-source { color: #BBB !important }
			#nette-debug nette-Doctrine2Panel tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>';
	}



	/**
	 * @param array
	 * @return string
	 */
	protected function processQuery(array $query)
	{
		$s = '';
		$h = 'htmlSpecialChars';
		list($sql, $params, $time, $rows, $connection, $source) = $query;

		$s .= '<tr><td>' . sprintf('%0.3f', $time * 1000);
		$s .= '</td><td class="nette-Doctrine2Panel-sql">' . Nette\Database\Helpers::dumpSql($sql);
		if ($source) {
			list($file, $line) = $source;
			$s .= Nette\Diagnostics\Helpers::editorLink($file, $line);
		}

		$s .= '</td><td>';
		$s .= Debugger::dump($params, TRUE);
		$s .= '</td><td>' . $rows . '</td></tr>';
		return $s;
	}



	/****************** Exceptions handling *********************/


	/**
	 * @param \Exception $e
	 * @return void|array
	 */
	public function renderException($e)
	{
		if ($e instanceof \PDOException && count($this->queries)) {
			list($sql, $params, , , , $source) = end($this->queries);

			return array('tab' => 'SQL', 'panel' => $this->dumpQuery($sql, $params),);
		}

		if ($e instanceof QueryException && $e->getQuery() !== NULL) {
			return array('tab' => 'DQL', 'panel' => $this->dumpQuery($e->getQuery()->getDQL(), $e->getQuery()->getParameters()),);
		}
	}



	/**
	 * @param string $query
	 * @param array $params
	 *
	 * @return array
	 */
	protected function dumpQuery($query, $params)
	{
		$h = 'htmlSpecialChars';

		// query
		$s = '<p><b>Query</b></p><table><tr><td class="nette-Doctrine2Panel-sql">';
		$s .= Nette\Database\Helpers::dumpSql($query);
		$s .= '</td></tr></table>';

		// parameters
		if ($params) {
			$s .= '<p><b>Parameters</b></p><table>';
			foreach ($params as $name => $value) {
				$s .= '<tr><td width="200">' . $h($name) . '</td><td>' . $h($value) . '</td></tr>';
			}
		}

		// styles and dump
		return $this->renderStyles() . '<div class="nette-inner nette-Doctrine2Panel">' . $s . '</table></div>';
	}



	/****************** Registration *********************/


	/**
	 * @return Panel
	 */
	public static function register()
	{
		$panel = new static;
		$panel->registerBarPanel(Debugger::$bar);
		$panel->registerBluescreen(Debugger::$blueScreen);
		return $panel;
	}



	/**
	 * Registers panel to debugger
	 *
	 * @param \Nette\Diagnostics\Bar $bar
	 */
	public function registerBarPanel(Bar $bar)
	{
		$bar->addPanel($this);
	}



	/**
	 * Registers panel in bluescreen
	 *
	 * @param \Nette\Diagnostics\BlueScreen $blueScreen
	 */
	public function registerBluescreen(BlueScreen $blueScreen)
	{
		$blueScreen->addPanel(callback($this, 'renderException'), __CLASS__);
	}

}