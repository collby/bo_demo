<?php

namespace app\admin\controller;

use think\Cache;
use think\Config;
use think\Controller;
use think\db\Query;
use think\Paginator;
use think\Request;
use app\common\model\DictCate as DictCateModel;
use app\common\model\FilmDictCate as FilmDictCateModel;
use app\common\model\FilmLabelType as FilmLabelTypeModel;

header("content-type:text/html;charset=utf-8");

class Common extends Controller
{

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
	}

	/**
	 * 排序属性
	 * field 可排序字段
	 * rule 排序规则，当多个字段排序时使用排序规则定义 [asc] 表示与前端选择排序方式一致 [desc] 表示与前端选择排序方式相反
	 * default_field 默认排序字段
	 * default_type 默认排序方式 1升序 0降序
	 * @var array
	 */
	protected $sort = [
		'field' => ['id' => '编号',],
		'rule' => [],
		'default_field' => 'id',
		'default_type' => 1,
	];

	/**
	 * 默认分页每页行数
	 * @var int
	 */
	protected $pageRows = 10;

	/**
	 * 空操作
	 * @param $name
	 * @return string
	 */
	public function _empty()
	{
		return '访问页面不存在';
	}

	/**
	 * 获取分页信息
	 * @param $list
	 * @return array
	 */
	protected function getPageInfo(Paginator $list)
	{
		return ['pageInfo' => json_encode([
			'total' => $list->total(),
			'currentPage' => intval($list->currentPage()),
			'lastPage' => $list->lastPage() > 0 ? $list->lastPage() : 1,
			'perPage' => $list->listRows(),
		])];
	}

	/**
	 * 获取排序信息
	 * @param Request $request
	 * @return mixed|string
	 */
	protected function getOrderBy(Request $request)
	{
		$field = $request->post('sort_field', $this->sort['default_field'], 'trim,htmlspecialchars');
		$type = $request->post('sort_type', $this->sort['default_type'], 'int');

		if (array_key_exists($field, $this->sort['field'])) {
			if (array_key_exists($field, $this->sort['rule'])) {
				$orderBy = $this->sort['rule'][$field];
			} else {
				$orderBy = $field . ' [asc]';
			}
		} else {
			$orderBy = $this->sort['default_field'] . ' ' . ($this->sort['default_type'] ? 'asc' : 'desc');
		}

		if ($type) {
			$orderBy = str_replace('[asc]', 'asc', $orderBy);
			$orderBy = str_replace('[desc]', 'desc', $orderBy);
		} else {
			$orderBy = str_replace('[asc]', 'desc', $orderBy);
			$orderBy = str_replace('[desc]', 'asc', $orderBy);
		}

		return $orderBy;
	}

	/**
	 * 获取每页行数
	 * @param Request $request
	 */
	protected function getPageRows(Request $request)
	{
		$pageRows = $request->post('page_rows', $this->pageRows, 'int');
		return $pageRows;
	}

	/**
	 * 获取搜索条件
	 * @param Request $request
	 * @return array
	 */
	protected function getCondition(Request $request)
	{
		$data = $request->param();
		$condition = array();
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				if (substr($key, 0, 10) === 'condition_' && count($value) > 0) {
					$condition[substr($key, 10)] = $value;
				}
			} else {
				$value = trim($value);
				if (substr($key, 0, 10) === 'condition_' && $value != '') {
					$condition[substr($key, 10)] = $value;
				}
			}
		}
		return $condition;
	}

	/**
	 * 批量操作
	 * @param Request $request
	 *
	 */
	protected function _batch(Request $request, $name)
	{
		$ids = $request->post('ids/a', [], 'trim,htmlspecialchars');
		$action = $request->post('action');

		if (empty($action)) {
			$this->error('未指定操作行为');
		}
		if (false === is_ids($ids, true)) {
			$this->error('请选择批量操作对象');
		}

		$count = model($name)
			->where('id', 'in', $ids)
			->count();

		if (intval($count) !== count($ids)) {
			$this->error('操作对象中存在无效项');
		}

		if (method_exists($this, '_' . $action)) {
			call_user_func(array($this, '_' . $action), $ids);
		} else {
			$this->error('操作行为不存在');
		}
	}

	/**
	 * 定义操作使用常量
	 */
	const CODE = '编号';
	const CREATE_TIME = '创建时间';
	const ADD_SUC = '新增成功';
	const ADD_ERR = '新增失败';
	const DEL_NOT_OBJ = '删除对象不存在';
	const DEL_OTHER_SUC = '部分删除成功';
	const CTL_NOT_OBJ = '操作对象不存在';
	const DEL_SUC = '删除成功';
	const DEL_ERR = '删除失败';
	const RUM_NOT_DEL = '启用状态记录不可删除!';
	const SAV_SUC = '保存成功';
	const SAV_ERR = '保存失败';
	const CTL_SUC = '操作成功';
	const FORBID_WARM = '是否确认禁用该记录,禁用后在维护数据时,将无法使用该字典项!';
	const UPLOAD_SUC = '上传成功';
	const UPLOAD_SAV_ERR = '图片保存失败';
	const UPLOAD_SAV_ERR1 = '测试';

}