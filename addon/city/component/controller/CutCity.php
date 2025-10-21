<?php

namespace addon\city\component\controller;

use app\component\controller\BaseDiyView;

/**
 * 城市模块·组件
 *
 */
class CutCity extends BaseDiyView
{
	
	/**
	 * 设计界面
	 */
	public function design()
	{
		return $this->fetch("city/design.html");
	}
}