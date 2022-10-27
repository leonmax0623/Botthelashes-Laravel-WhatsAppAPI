<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>🤖 Admin Bot</title>

    <!-- Bootstrap core CSS -->

    <link href="/gentelella/production/css/bootstrap.min.css" rel="stylesheet">

    <link href="/gentelella/production/fonts/css/font-awesome.min.css" rel="stylesheet">
    <link href="/gentelella/production/css/animate.min.css" rel="stylesheet">

    <!-- Custom styling plus plugins -->
    <link href="/gentelella/production/css/custom.css" rel="stylesheet">
    <link href="/gentelella/production/css/icheck/flat/green.css" rel="stylesheet">

    <link href="/gentelella/production/js/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <link href="/gentelella/production/js/datatables/buttons.bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="/gentelella/production/js/datatables/fixedHeader.bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="/gentelella/production/js/datatables/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="/gentelella/production/js/datatables/scroller.bootstrap.min.css" rel="stylesheet" type="text/css" />

    <script src="/gentelella/production/js/jquery.min.js"></script>

    <!--[if lt IE 9]>
    <script src="../assets/js/ie8-responsive-file-warning.js"></script>
    <![endif]-->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>
<body class="nav-md">

<div class="container body">

    <div class="main_container">




        <div class="col-md-3 left_col">
            <div class="left_col scroll-view">
                <div class="navbar nav_title" style="border: 0;">
                    <a href="/home" class="site_title"> 🤖 <span> TheLashes Bot</span></a>
                </div>
                <div class="clearfix"></div>
                <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                    <div class="menu_section">
                        <ul class="nav side-menu">
                            <li><a href="#"><i class="fa fa-bullhorn"></i> Главная</a></li>
{{--                            <li><a href="/sms"><i class="fa fa-envelope-o"></i> SMS</a></li>--}}
{{--                            <li><a href="/masters"><i class="fa fa-users"></i> Мастера</a></li>--}}
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        <!-- top navigation -->
        <div class="top_nav">

            <div class="nav_menu">
                <nav class="" role="navigation">
                    <div class="nav toggle">
                        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                    </div>

                    <ul class="nav navbar-nav navbar-right">
                        <li class="">
                            <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name }}
                                <span class=" fa fa-angle-down"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-usermenu animated fadeInDown pull-right">
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                                        <i class="fa fa-sign-out pull-right"></i> Log Out
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
            @yield('content')
        </div>
        <!-- /page content -->
    </div>

</div>



<div id="custom_notifications" class="custom-notifications dsp_none">
    <ul class="list-unstyled notifications clearfix" data-tabbed_notifications="notif-group">
    </ul>
    <div class="clearfix"></div>
    <div id="notif-group" class="tabbed_notifications"></div>
</div>

<script src="/gentelella/production/js/bootstrap.min.js"></script>

<!-- bootstrap progress js -->
<script src="/gentelella/production/js/progressbar/bootstrap-progressbar.min.js"></script>
<script src="/gentelella/production/js/nicescroll/jquery.nicescroll.min.js"></script>
<!-- icheck -->
<script src="/gentelella/production/js/icheck/icheck.min.js"></script>

<script src="/gentelella/production/js/custom.js"></script>


<!-- Datatables -->
<!-- <script src="js/datatables/js/jquery.dataTables.js"></script>
  <script src="js/datatables/tools/js/dataTables.tableTools.js"></script> -->

<!-- Datatables-->
<script src="/gentelella/production/js/datatables/jquery.dataTables.min.js"></script>
<script src="/gentelella/production/js/datatables/dataTables.bootstrap.js"></script>
<script src="/gentelella/production/js/datatables/dataTables.buttons.min.js"></script>
<script src="/gentelella/production/js/datatables/buttons.bootstrap.min.js"></script>
<script src="/gentelella/production/js/datatables/jszip.min.js"></script>
<script src="/gentelella/production/js/datatables/pdfmake.min.js"></script>
<script src="/gentelella/production/js/datatables/vfs_fonts.js"></script>
<script src="/gentelella/production/js/datatables/buttons.html5.min.js"></script>
<script src="/gentelella/production/js/datatables/buttons.print.min.js"></script>
<script src="/gentelella/production/js/datatables/dataTables.fixedHeader.min.js"></script>
<script src="/gentelella/production/js/datatables/dataTables.keyTable.min.js"></script>
<script src="/gentelella/production/js/datatables/dataTables.responsive.min.js"></script>
<script src="/gentelella/production/js/datatables/responsive.bootstrap.min.js"></script>
<script src="/gentelella/production/js/datatables/dataTables.scroller.min.js"></script>


<!-- pace -->
<script src="/gentelella/production/js/pace/pace.min.js"></script>
<script>
    var handleDataTableButtons = function() {
            "use strict";
            0 !== $("#datatable-buttons").length && $("#datatable-buttons").DataTable({
                dom: "Bfrtip",
                buttons: [{
                    extend: "copy",
                    className: "btn-sm"
                }, {
                    extend: "csv",
                    className: "btn-sm"
                }, {
                    extend: "excel",
                    className: "btn-sm"
                }, {
                    extend: "pdf",
                    className: "btn-sm"
                }, {
                    extend: "print",
                    className: "btn-sm"
                }],
                "pageLength" : 50,
                responsive: !0
            })
        },
        TableManageButtons = function() {
            "use strict";
            return {
                init: function() {
                    handleDataTableButtons()
                }
            }
        }();
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#datatable').dataTable();
        $('#datatable-keytable').DataTable({
            keys: true
        });
        $('#datatable-responsive').DataTable();
        $('#datatable-scroller').DataTable({
            ajax: "js/datatables/json/scroller-demo.json",
            deferRender: true,
            scrollY: 380,
            scrollCollapse: true,
            scroller: true
        });
        var table = $('#datatable-fixed-header').DataTable({
            fixedHeader: true
        });
    });
    TableManageButtons.init();
</script>
</body>

</html>