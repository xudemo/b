<?php
namespace app\my\model;

use think\Db;

//财务详情
class FinanceDetails
{
    public function getFinance($id, $type)
    {
        $result = [];
        switch ($type) {
            case 'kj':
                break;
            case 'hb':
				$result = $this->getHbFinance($id);
                break;
        }
        return $result;
    }

    public function getKjFinance($id)
    {

    }
    //总支付数 总支付金额 总红包数 总红包金额 退还金额 利润
    public function getHbFinance($id)
    {
        $orderSql = "select count(*) as payNum,sum(money) as money from zn_hb_orders where hid={$id}";
        $list['order'] = Db::query($orderSql);
        $integralSql = "select count(*) as integralNum,sum(integral) as integral,(select sum(integral) from zn_hb_integral where hid={$id} and zn_hb_integral.use=5) as sendIntegral from zn_hb_integral where hid={$id}";
        $list['integral'] = Db::query($integralSql);
        $list['lirun'] = $list['order'][0]['money']-$list['integral'][0]['integral']+$list['integral'][0]['sendIntegral'];
        return $list;
    }
}