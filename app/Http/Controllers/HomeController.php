<?php

namespace App\Http\Controllers;

use App\Core\Events\YclientsRequest;
use App\Core\PaymentModel;
use App\Yclients\Cache\AdminConfig;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('access');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function saveConfig(Request $request){
        $paymentMode = $request->input('paymentMode');
        $paymentType = $request->input('paymentType');
        $paymentAmount = $request->input('paymentAmount');
        $dateStart = $request->input('dateStart');
        $dateGlobalMustPrepaymentStart = $request->input('dateGlobalMustPrepaymentStart');
        $dateFinish = $request->input('dateFinish');
        $dateGlobalMustPrepaymentFinish = $request->input('dateGlobalMustPrepaymentFinish');
        $connectYclients = $request->input('connectYclients');
        $timeEvening = $request->input('timeEvening');
        $timeMorning = $request->input('timeMorning');
        $prepaymentForNewClient = $request->input('prepaymentForNewClient');
        $config = [
            'paymentMode' => $paymentMode,
            'paymentType' => $paymentType,
            'paymentAmount' => $paymentAmount,
            'dateStart' => $dateStart,
            'dateGlobalMustPrepaymentStart' => $dateGlobalMustPrepaymentStart,
            'dateFinish' => $dateFinish,
            'dateGlobalMustPrepaymentFinish' => $dateGlobalMustPrepaymentFinish,
            'connectYclients' => $connectYclients,
            'timeEvening' => $timeEvening,
            'timeMorning' => $timeMorning,
            'prepaymentForNewClient' => $prepaymentForNewClient,
        ];
        PaymentModel::configUpdate($config);
        AdminConfig::save();
        return redirect("/home");
    }

    public function index()
    {
        $config = json_decode(PaymentModel::getPaymentConfig()->value,1);
        $data['paymentMode'] = $config['paymentMode'];
        $data['paymentType'] = $config['paymentType'];
        $data['paymentAmount'] = $config['paymentAmount'];
        $data['dateStart'] = $config['dateStart'];
        $data['dateGlobalMustPrepaymentStart'] = $config['dateGlobalMustPrepaymentStart'];
        $data['dateGlobalMustPrepaymentFinish'] = $config['dateGlobalMustPrepaymentFinish'];
        $data['dateFinish'] = $config['dateFinish'];
        $data['connectYclients'] = $config['connectYclients'];
        $data['timeEvening'] = $config['timeEvening'];
        $data['timeMorning'] = $config['timeMorning'];
        $data['prepaymentForNewClient'] = $config['prepaymentForNewClient'];
        return view('home', $data);
    }
}
