<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>
        <style>
            .fc-title-left-aligned {
                text-align: left;
            }
        </style>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">

                            <div class="h-100">
                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Tour Calendar</h4>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div id="calendar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div> <!-- end .h-100-->

                            <div class="modal fade" id="Viewtour" tabindex="-1" aria-labelledby="ViewtourModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="ViewtourModalLabel">Tour Details</h1>
                                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                                            <input type="hidden" name="tour_id" id="tour_id">
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div>
                                                    <label class="form-label" for="tour">Tour ID : </label><span id="tour"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="tour_name">Tour Name : </label><span id="tour_name"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="tour_start">Tour Start Date : </label><span id="tour_start"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="tour_end">Tour End Date : </label><span id="tour_end"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="tour_agent">Tour Agent : </label><span id="tour_agent"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="vehicle">Selected Vehicle : </label><span id="vehicle"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="vehicle_rate">Vehicle Rate : </label><span id="vehicle_rate"></span>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="pax_count">Passenger Count : </label><span id="pax_count"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok-itinerary">Close</button>
                                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script src="<?= base_url() ?>assets/moment/min/moment.min.js"></script>
    <script src="<?= base_url() ?>assets/bootstrap-daterangepicker/daterangepicker.js"></script>

    <script>
        // $(".default-daterange").daterangepicker({
        //     opens: "right",
        //     format: "YYYY-MM-DD",
        //     separator: " to ",
        //     startDate: moment(),
        //     endDate: moment(),
        // }, function(start, end) {
        //     $(".default-daterange input").val(start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"));
        //     var date = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");
        //     alert(date);
        // });
    </script>

    <script type="text/javascript" src="<?= base_url() ?>assets/calender/zabuto_calendar.min.js"></script>
    <script src="<?= base_url() ?>assets/fullcalendar/dist/fullcalendar.js"></script>

    <script type="text/javascript">
        // type = $('#ev_type').val();

        $calendar = $('#calendar');
        var base_url = '<?= base_url() ?>';
        today = new Date();
        y = today.getFullYear();
        m = today.getMonth();
        d = today.getDate();
        var viewtitle = 'month';
        var pagetitle = "";

        $calendar.fullCalendar({
            viewRender: function(view, element) {},

            header: {
                center: 'title',
                right: 'month',
                left: 'prev,next,today'
            },
            defaultDate: today,
            defaultView: viewtitle,
            selectable: true,
            selectHelper: true,
            views: {
                month: {
                    titleFormat: 'MMMM YYYY'
                },
                week: {
                    titleFormat: " MMMM D YYYY"
                },
                day: {
                    titleFormat: 'D MMM, YYYY'
                }
            },
            timezone: "Asia/Colombo",
            draggable: false,

            editable: false,
            eventLimit: false,
            events: {
                url: base_url + 'tour/calendar/getevents',
            },

            eventRender: function(event, element) {

                // Define text with block elements
                var titleText = 'Tour Name: ' + event.title;
                var tourIdText = 'Tour ID: ' + event.manu_id;
                var agentText = 'Agent: ' + event.agent;
                var startdate = event.start.format('YYYY-MM-DD');
                var enddate = event.end.format('YYYY-MM-DD');
                var onclick = 'view_event(' + event.tour_id + ')';

                // Update the HTML content of the .fc-title element with divs
                element.find('.fc-title').html('<div class="fc-title-left-aligned">' + titleText + '</div><div class="fc-title-left-aligned">' + tourIdText + '</div><div class="fc-title-left-aligned">' + startdate + ' to ' + enddate + '</div>');

                // Set the title attribute with descriptive text
                element.attr('title', titleText + ' (' + tourIdText + ')');

                // Set the onclick attribute
                element.attr('onclick', event.onclick);

                // Enable Bootstrap tooltip
                element.attr('data-toggle', 'tooltip');

                element.click(function() {
                    view_event(event.tour_id);
                });
            },

            dayClick: function(date, jsEvent, view) {
                var d = date.format();
                if (!$.fullCalendar.moment(d).hasTime()) {
                    d += ' 05:30';
                }

                $("#input-field").val('');
                $("#desc-field").text('');
                $("#date-field").daterangepicker({
                    startDate: date,
                    endDate: date,
                    timePicker: false,
                    timePickerIncrement: 5,
                    locale: {
                        format: 'MM/DD/YYYY hh:mm a'
                    }
                });
                // $('#newEventModal').modal('show');

                return false;
            }

        });


        $(document).ready(function() {
            $("#date-field").daterangepicker({
                timePicker: true,
                timePickerIncrement: 5,
                locale: {
                    format: 'MM/DD/YYYY hh:mm A'
                }
            });
        });

        // view event function 
        function view_event(tour_id) {
            $('#tour_id').val(tour_id);
            $.ajax({
                url: '<?php echo base_url(); ?>tour/calendar/details',
                type: 'GET',
                data: {
                    tour: tour_id,
                },
                dataType: 'json',
                beforeSend: function() {},
                success: function(response) {
                    // $('#tour').text(response.data.tour_id);
                    $('#tour').text(response.data.manual_id);
                    $('#tour_start').text(response.data.start_date);
                    $('#tour_end').text(response.data.end_date);
                    $('#tour_name').text(response.data.consignee);
                    $('#tour_agent').text(response.data.agent_name);
                    $('#vehicle').text(response.data.vehicle_name);
                    $('#vehicle_rate').text(response.data.vehicle_rate);
                    $('#pax_count').text(response.data.passenger_count);

                    $('#Viewtour').modal('show');
                },
                error: function(jqXHR, textStatus, errorThrown) {

                }
            });
        }

        function datepic() {
            $("#date-field").daterangepicker();
        }
    </script>



</body>

</html>