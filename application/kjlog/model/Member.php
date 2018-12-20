<?php
namespace app\kjlog\model;

use think\Db;

class Member
{
    //用户的所有信息
    public static function get_member($hid)
    {
        $start_time = array_sum(explode(' ', microtime()));
        $member = cookie('member');
        //已有多少人帮忙砍
        $data['k_num'] = Db::name('kj_kjlog')->where(['uid' => $member['id']])->count();
        //砍了多少钱
        $data['k_money'] = $member['dmoney'] - $member['money'];
        //我的团员
        $data['myMember'] = Db::name('kj_member')->where(['tid' => $member['id']])->paginate(10,false,['query'=>['hid'=>$hid]]);
        $data['page'] = $data['myMember']->render();
        //帮我砍价的
        // $data['friend'] = Db::name('kj_kjlog')->where(['uid' => $member['id']])->order('id desc')->limit(0, 5)->select();
        // for ($a = 0; $a < count($data['friend']); $a++) {
        //     $data['friend'][$a]['member'] = Db::name('kj_member')->where(['id' => $data['friend'][$a]['pid']])->field('nickname,pic')->find();
        // }
        $field = "*,(select pic from zn_kj_member where id=a.pid) as pic,(select nickname from zn_kj_member where id=a.pid) as nickname";
        $data['friend'] = Db::name('kj_kjlog')
            ->alias('a')
            ->where(['a.uid' => $member['id']])
            ->order('a.id desc')
            ->limit(0, 5)
            ->field($field)
            ->select();
        //我的团所有人
        $data['num'] = Db::name('kj_member')->where(['tid' => $member['id']])->count();
        //我的团已支付人数
        $data['pay_num'] = Db::name('kj_member')->where(['tid' => $member['id'], 'pay' => 1])->count();
        //我的团未支付人数
        $data['nopay_num'] = $data['num'] - $data['pay_num'];
        //我的团长姓名
        $m = Db::name('kj_member')->where(['id' => $member['tid']])->field('name')->find();
        $data['t_name'] = $m['name'];
        //我所在团所有人
        $data['tnum'] = Db::name('kj_member')->where(['tid' => $member['tid']])->count();
        //团已支付人数
        $data['tpay_num'] = Db::name('kj_member')->where(['tid' => $member['tid'], 'pay' => 1])->count();
        //当前价
        $data['d_money'] = $member['money'];
        $end_time = array_sum(explode(' ', microtime()));
        $differ = $end_time - $start_time;
        // dump($differ);
        // exit;
        return $data;
    }

    //帮人的用户信息
    public static function getMember($oneself, $sid)
    {
        //已有多少人帮忙砍
        $data['k_num'] = Db::name('kj_kjlog')->where(['uid' => $oneself['id']])->count();
        //省了多少钱
         $data['k_money'] = $oneself['dmoney'] - $oneself['money'];
        // $data['friend'] = Db::name('kj_kjlog')->where(['uid' => $sid])->order('id desc')->limit(0, 5)->select();
        // for ($a = 0; $a < count($data['friend']); $a++) {
        //     $data['friend'][$a]['member'] = Db::name('kj_member')->where(['id' => $data['friend'][$a]['pid']])->field('nickname,pic')->find();
        // }
        $field = "*,(select pic from zn_kj_member where id=a.pid) as pic,(select nickname from zn_kj_member where id=a.pid) as nickname";
        $data['friend'] = Db::name('kj_kjlog')
            ->alias('a')
            ->where(['a.uid' => $sid])
            ->order('a.id desc')
            ->limit(0, 5)
            ->field($field)
            ->select();
        //当前价
        $data['d_money'] = $oneself['money'];
        return $data;
    }
}