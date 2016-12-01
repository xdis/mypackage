<?php
/**
 *  InterestController.php
 *
 * @author gengzhiguo@xiongmaojinfu.com
 * $Id: NotifyController.php 2016-06-23 下午1:17 $
 */


namespace Zigo928\Mypackage\App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class InterestController extends BaseController
{

    protected $interestTypes = [
        1,
        //按日计息 按月付息最后还本
        2,
        //按月计息 按月付息最后还本
        3,
        //等额本息
        4,
        //按日计息 一次性还本付息
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        try {
            //验证手机号
            $rules = [
                'interest_type' => 'required|numeric|in:1,2,3,4',
                'amount'        => 'required|integer|min:100',
                'rate'          => 'required|numeric|between:0,1',
                'period'        => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                $resp = [
                    'code' => '2301',
                    'msg'  => config('messages.2301') . ':' . $error,
                ];

                return response()->json($resp);
            }

            $interestType = $request->get('interest_type', 0);
            $period = $request->get('period');
            $amount = $request->get('amount');
            $rate = $request->get('rate');

            switch ($interestType) {
                case 1:
                    $interestInfo = dayInterestPrincipal($amount, $rate, $period);
                    break;
                case 2:
                    $interestInfo = monthInterestPrincipal($amount, $rate, $period);
                    break;
                case 3:
                    $interestInfo = equalPrincipalInterest($amount, $rate, $period);
                    break;
                case 4:
                    $interestInfo = dayOnceInterestPrincipal($amount, $rate, $period);
                    break;
                default:
                    $resp = [
                        'code' => 2302,
                        'msg'  => config('messages.2302'),
                    ];

                    return response()->json($resp);
            }

            $resp = [
                'code' => 0,
                'msg'  => '成功',
                'data' => $interestInfo,
            ];

            return response()->json($resp);
        } catch (\Exception $e) {
            Log::error('c=interest f=index error:' . $e);
            $resp = [
                'code' => 1002,
                'msg'  => config('messages.1002'),
            ];

            return response()->json($resp);
        }
    }
}
