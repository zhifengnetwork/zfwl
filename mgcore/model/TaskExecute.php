<?php
/**
 * 订单处理任务模型
 *
 */

namespace mgcore\model;

use think\Config;
use think\Model;

class TaskExecute extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    //表名
    private $_table = 'task_execute';

    //任务执行结果 1-成功 0-失败
    const EXE_RESULT_SUCC = 1;
    const EXE_RESULT_FAIL = 0;

    //任务状态 0-等待执行 1-已执行
    const TASK_WAIT_STATUS     = 0;
    const TASK_EXECUTED_STATUS = 1;

    const NUMBER_PER_TIEM = 50;

    //是否已发送警报 1-已发送 2-未发送
    const SEND_WARMING_YES = 1;
    const SEND_WARMING_NO  = 0;

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 增加一条异步任务
     *  @param int $nameSpace     命名空间
     *  @param int $class         类名
     *  @param Str $function      方法
     *  @param int $params        参数
     *  @param Str $remark        备注
     * @return boolean
     */
    public function addTask($taskNo, $params, $remark = '', $sequence = 1)
    {
        if (!$taskNo) {
            return false;
        }
        $data = [
            'task_num'    => $taskNo,
            'params'      => $params,
            'remark'      => $remark,
            'sequence'    => $sequence,
            'create_time' => time(),
        ];
        $res = $this->table($this->_table)->insert($data);
        return $res;
    }

    /**
     * 获取任务列表
     *  @param int $status     任务状态
     *  @param int $result     任务结果
     *  @param Str $field      字段
     *  @param int $ordeBy     排序字段
     * @return boolean
     */
    public function getTaskList($status = self::TASK_WAIT_STATUS, $result = self::EXE_RESULT_FAIL, $field = "*", $ordeBy = 'sequence ASC, id DESC')
    {
        $where = [
            'status' => (int) $status,
            'result' => (int) $result,
        ];
        $res = $this->table($this->_table)->field($field)->where($where)->limit(0, self::NUMBER_PER_TIEM)->order($ordeBy)->select();
        return $res;
    }

    /**
     * 更新任务
     *  @param int $status     任务状态
     *  @param int $result     任务结果
     *  @param int $count     排序字段
     * @return boolean
     */
    public function updateTask($taskId, $status, $result, $error = '')
    {
        $data = [
            'status'      => $status,
            'result'      => $result,
            'count'       => ['exp', 'count + 1'],
            'update_time' => time(),
        ];
        $error && $data['error'] = $error;
        $res                     = $this->table($this->_table)->where(['id' => $taskId])->update($data);
        return $res;
    }
}
