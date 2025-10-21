<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace app\admin\controller;


/**
 * 商家等级管理 控制器
 */
class Shoplevel extends BaseAdmin
{
	/**
	 * 等级列表
	 */
	public function lists()
	{
		return $this->fetch('shoplevel/lists');
	}
	
	/**
	 * 等级添加
	 */
	public function addLevel()
	{
		return $this->fetch('shoplevel/add_level');
	}
	
	/**
	 * 等级编辑
	 */
	public function editLevel()
	{
		return $this->fetch('shoplevel/edit_level');
	}
	
	/**
	 * 等级删除
	 */
	public function deleteLevel()
	{
	
	}
}