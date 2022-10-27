@extends('layouts.template')

@section('content')
    <style>
        .m0{margin:0;}
        .p0{padding:0;}
        textarea {width:100%; height:100px; margin-bottom:20px;}
        .form-group {
            min-height: 35px;
        }
    </style>
    <form action="" method="post">
        <div class="">
            {{--        <div class="page-title">--}}
            {{--            <div class="title_left">--}}
            {{--                <h3>--}}
            {{--                    Настройки<small></small>--}}
            {{--                </h3>--}}
            {{--            </div>--}}
            {{--        </div>--}}
            <div class="clearfix"></div>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_content">
                            <h3>Общие настройки</h3>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12 m0 p0">НОЧНОЕ время по мск</label>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    От времени вечером <input type="time" required name="timeEvening" value="{{$timeEvening}}"/>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    До времени утром <input type="time" required name="timeMorning" value="{{$timeMorning}}"/>
                                </div>
                                <div class="clearfix"></div>
                                <p>
                                    В ночное время отключено: <br>
                                    - напоминание о предоплате <br>
                                    - удаление в случае отсутствия внесения предоплаты
                                </p>
                            </div>

                            <hr>
                            <h3>Модуль Предоплаты</h3>


                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Режим предоплаты</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <select class="select2_single form-control" tabindex="-1" name="paymentMode">
                                        <option value="1" @if($paymentMode == 1) selected @endif>Включен всегда и для всех</option>
                                        <option value="2" @if($paymentMode == 2) selected @endif>Включен на основе скоринга</option>
                                        <option value="0" @if($paymentMode == 0) selected @endif>Выключен</option>
                                    </select>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Диапазон даты визита</label>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    От даты <input type="date" required name="dateStart" value="{{$dateStart}}"/>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    До даты <input type="date" required name="dateFinish" value="{{$dateFinish}}"/>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Тип предоплаты</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <select class="select2_single form-control" tabindex="-1" name="paymentType">
                                        <option value="fix" @if($paymentType == 'fix') selected @endif>Фиксированная сумма, руб</option>
                                        <option value="percent" @if($paymentType == 'percent') selected @endif>Процент от суммы визита</option>
                                    </select>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Значение</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="number" required name="paymentAmount" value="{{$paymentAmount}}" id="autocomplete-custom-append" class="form-control col-md-10">
                                </div>
                            </div>


                            <div class="clearfix"></div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Предоплата для Новых Клиентов</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <select class="select2_single form-control" tabindex="-1" name="prepaymentForNewClient">
                                        <option value="0" @if($prepaymentForNewClient == '0') selected @endif>Отключен</option>
                                        <option value="1" @if($prepaymentForNewClient == '1') selected @endif>Включен</option>
                                    </select>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <p>Важно: Модуль предоплаты будет активен только для записей, который находятся в пределах <b>Диапазон даты визита</b></p>
                            <p>Чтобы модуль предоплаты был активирован ВСЕГДА (без ограничений по сроку), необходимо в поле <b>«До даты»</b> указать на несколько лет вперед, например до 01.01.2120.</p>

                            <hr>
                            <h3>Диапазон дат, когда ВСЕГДА берем предоплату, кроме тега "не требуется предоплата"</h3>

                            <div class="clearfix"></div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Диапазон даты визита</label>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    От даты <input type="date" required name="dateGlobalMustPrepaymentStart" value="{{@$dateGlobalMustPrepaymentStart}}"/>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    До даты <input type="date" required name="dateGlobalMustPrepaymentFinish" value="{{@$dateGlobalMustPrepaymentFinish}}"/>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="clearfix"></div>
            <input type="submit" class="btn btn-primary" value="💾 Сохранить все изменения">
        </div>

    </form>
@endsection
