<?php

namespace addon\presale\component\controller;

use app\component\controller\BaseDiyView;

/**
 * 预售模块·组件
 *
 */
class Presale extends BaseDiyView
{

    /**
     * 设计界面
     */
    public function design()
    {
        return $this->fetch("presale/design.html");
    }
}