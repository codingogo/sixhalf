jQuery(document).ready(function($){function zoomChart(){var a=top_sales_by_date_chart.dataProvider.length;a>30&&top_sales_by_date_chart.zoomToIndexes(a-30,a-1),(a=payed_by_date_chart.dataProvider.length)>30&&payed_by_date_chart.zoomToIndexes(a-30,a-1)}function get_dashboard_data(from,to){block($("#mnp-dashboard-wrapper")),$.ajax({type:"post",dataType:"json",url:mnp_dashboard.ajax_url,data:{action:mnp_dashboard.dashboard_action,command:"dashboard_data",date_from:from,date_to:to,interval:$(".search-interval.selected").data("interval")},success:function(response){response.success?($.each(response.data,function(key,value){var fn="process_"+key;eval("typeof "+fn+" == 'function'")&&eval(fn)(value)}),zoomChart(),unblock($("#mnp-dashboard-wrapper"))):(alert(response.data.message),unblock($("#mnp-dashboard-wrapper")))},error:function(){unblock($("#mnp-dashboard-wrapper"))}})}function process_order_stat(a){$(".sales_status .mnp_singlestat_panel .count").html("0"),$(".sales_status .mnp_singlestat_panel .amount").html("0"),$.each(a,function(a,b){$(".mnp-dashboard-stat ."+a+" .count").html(b.count),$(".mnp-dashboard-stat ."+a+" .amount").html(b.amount)})}function process_order_stat_by_status(a){$(".mnp-dashboard-stat .order-status .count").html("0"),$(".mnp-dashboard-stat .order-status .amount").html("0"),$.each(a,function(a,b){$(".mnp-dashboard-stat ."+a+" .count").html(b.count),$(".mnp-dashboard-stat ."+a+" .amount").html(b.amount)})}function process_claim_stat(a){$(".mnp-dashboard-stat .count").removeClass("alert"),$.each(a,function(a,b){$(".mnp-dashboard-stat ."+a+" .count").removeClass("mnp_alert"),$(".mnp-dashboard-stat ."+a+" .count").html(b.count),b.count>0&&($(".mnp-dashboard-stat ."+a+" .count").html('<a target=blank href="/wp-admin/edit.php?post_status=wc-'+a.toLowerCase().replace("_","-")+'&post_type=shop_order">'+b.count+"</a>"),$(".mnp-dashboard-stat ."+a+" .count").addClass("mnp_alert"))})}function process_order_stat_by_date(a){_.find(top_sales_by_date_chart.graphs,function(a){return"amount"==a.id}).title=$(".search-interval.selected").data("amount_label"),_.find(top_sales_by_date_chart.graphs,function(a){return"count"==a.id}).title=$(".search-interval.selected").data("count_label"),top_sales_by_date_chart.dataProvider=a,top_sales_by_date_chart.validateData()}function process_payed_stat_by_date(a){payed_by_date_chart.dataProvider=a,payed_by_date_chart.validateData()}function process_sales_stat_by_product(a){$("table.sales_by_product tbody").empty(),$.each(a.order_by_amount,function(a,b){var c="amount_spartline_"+b.id,d=(b.id,'<div id="'+c+'" style="vertical-align: middle;display: inline-block; width: 110px; height: 30px;"></div></div>');$("table.sales_by_product.amount tbody").append('<tr><td class="title">'+b.name+'</td><td class="value">'+b.value+'</td><td class="spartline">'+d+"</td></tr>");var e=JSON.parse(JSON.stringify(spartLineParams));e.dataProvider=b.trends,AmCharts.makeChart(c,e)}),$.each(a.order_by_qty,function(a,b){var c="qty_spartline_"+b.id,d='<div id="'+c+'" style="vertical-align: middle;display: inline-block; width: 110px; height: 30px;">';$("table.sales_by_product.qty tbody").append('<tr><td class="title">'+b.name+'</td><td class="value">'+b.value+'</td><td class="spartline">'+d+"</td></tr></tr>");var e=JSON.parse(JSON.stringify(spartLineParams2));e.dataProvider=b.trends,AmCharts.makeChart(c,e),AmCharts.makeChart(c,e)})}var is_blocked=function(a){return a.is(".processing")||a.parents(".processing").length},block=function(a){is_blocked(a)||a.addClass("processing").block({message:null,overlayCSS:{background:"#fff",opacity:.6}})},unblock=function(a){a.removeClass("processing").unblock()},top_sales_by_date_chart=AmCharts.makeChart("top_sales_by_date_chart",{type:"serial",theme:"light",legend:{useGraphSettings:!0,markerSize:10,valueText:"",valueWidth:0},addClassNames:!0,startDuration:1,mouseWheelZoomEnabled:!1,dataDateFormat:"YYYY-MM-DD",valueAxes:[{id:"v1",axisAlpha:0,position:"left"},{id:"countAxis",axisAlpha:0,gridAlpha:0,position:"right"}],balloon:{borderThickness:1,shadowAlpha:0},graphs:[{id:"amount_sum",fillAlphas:1,valueField:"amount_sum",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>"+mnp_dashboard.currency+"[[value]]</span> [[additional]]</span>",type:"column",title:"기간별 총매출"},{id:"amount",fillAlphas:1,valueField:"value",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>"+mnp_dashboard.currency+"[[value]]</span> [[additional]]</span>",type:"column",clustered:!1,columnWidth:.5,title:"당일매출"},{id:"count",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>[[value]]</span> [[additional]]</span>",bullet:"round",lineThickness:1,bulletSize:2,bulletBorderAlpha:1,bulletColor:"#FFFFFF",useLineColorForBulletBorder:!0,bulletBorderThickness:1,fillAlphas:0,lineAlpha:1,title:"당일구매건수",valueField:"count",valueAxis:"countAxis",animationPlayed:!0},{id:"count_sum",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>[[value]]</span> [[additional]]</span>",bullet:"round",lineThickness:1,bulletSize:2,bulletBorderAlpha:1,bulletColor:"#FFFFFF",useLineColorForBulletBorder:!0,bulletBorderThickness:1,fillAlphas:0,lineAlpha:1,title:"기간별 총구매건수",valueField:"count_sum",valueAxis:"countAxis",type:"smoothedLine",animationPlayed:!0}],chartScrollbar:{graph:"amount_sum",oppositeAxis:!1,offset:10,scrollbarHeight:40,dragIconHeight:20,backgroundAlpha:0,selectedBackgroundAlpha:.1,selectedBackgroundColor:"#888888",graphFillAlpha:0,graphLineAlpha:.5,selectedGraphFillAlpha:0,selectedGraphLineAlpha:1,autoGridCount:!1,color:"#AAAAAA"},chartCursor:{pan:!0,valueLineEnabled:!0,valueLineBalloonEnabled:!0,cursorAlpha:1,cursorColor:"#258cbb",limitToGraph:"amount",valueLineAlpha:.2,valueZoomable:!0},categoryField:"date",categoryAxis:{equalSpacing:!0,parseDates:!0,dashLength:1,minorGridEnabled:!0},export:{enabled:!0},dataProvider:[]}),payed_by_date_chart=AmCharts.makeChart("payed_by_date_chart",{type:"serial",theme:"light",legend:{useGraphSettings:!0,markerSize:10,valueText:"",valueWidth:0},addClassNames:!0,startDuration:1,mouseWheelZoomEnabled:!1,dataDateFormat:"YYYY-MM-DD",valueAxes:[{id:"v1",axisAlpha:0,position:"left"},{id:"countAxis",axisAlpha:0,gridAlpha:0,position:"right"}],balloon:{borderThickness:1,shadowAlpha:0},graphs:[{id:"g2",fillAlphas:.8,valueField:"payed_amount_sum",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>"+mnp_dashboard.currency+"[[value]]</span> [[additional]]</span>",type:"column",title:"결제완료",lineColor:"#FF9E01",fillColors:"#FF9E01"},{id:"g1",fillAlphas:1,valueField:"sales_amount_sum",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>"+mnp_dashboard.currency+"[[value]]</span> [[additional]]</span>",type:"column",clustered:!1,columnWidth:.5,title:"구매확정",lineColor:"#67b7dc",fillColors:"#67b7dc"},{id:"g4",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>[[value]]</span> [[additional]]</span>",bullet:"round",lineThickness:1,bulletSize:2,bulletBorderAlpha:1,bulletColor:"#FFFFFF",useLineColorForBulletBorder:!0,bulletBorderThickness:1,fillAlphas:0,lineAlpha:1,title:"결제완료건",valueField:"payed_count_sum",valueAxis:"countAxis",animationPlayed:!0},{id:"g3",balloonText:"<span style='font-size:10px;'>[[title]] <span style='font-size:12px; font-weight: bold;'>[[value]]</span> [[additional]]</span>",bullet:"round",lineThickness:1,bulletSize:2,bulletBorderAlpha:1,bulletColor:"#FFFFFF",useLineColorForBulletBorder:!0,bulletBorderThickness:1,fillAlphas:0,lineAlpha:1,title:"구매확정건",valueField:"sales_count_sum",valueAxis:"countAxis",type:"smoothedLine",animationPlayed:!0}],chartScrollbar:{graph:"g2",oppositeAxis:!1,offset:10,scrollbarHeight:40,dragIconHeight:20,backgroundAlpha:0,selectedBackgroundAlpha:.1,selectedBackgroundColor:"#888888",graphFillAlpha:0,graphLineAlpha:.5,selectedGraphFillAlpha:0,selectedGraphLineAlpha:1,autoGridCount:!1,color:"#AAAAAA"},chartCursor:{pan:!0,valueLineEnabled:!0,valueLineBalloonEnabled:!0,cursorAlpha:1,cursorColor:"#258cbb",limitToGraph:"g1",valueLineAlpha:.2,valueZoomable:!0},categoryField:"date",categoryAxis:{equalSpacing:!0,parseDates:!0,dashLength:1,minorGridEnabled:!0},export:{enabled:!0},dataProvider:[]}),spartLineParams={type:"serial",categoryField:"date",autoMargins:!1,marginLeft:0,marginRight:0,marginTop:0,marginBottom:0,graphs:[{valueField:"value",type:"column",fillAlphas:1,lineColor:"#a9ec49",showBalloon:!1}],valueAxes:[{gridAlpha:0,axisAlpha:0}],categoryAxis:{gridAlpha:0,axisAlpha:0}},spartLineParams2={type:"serial",categoryField:"date",autoMargins:!1,marginLeft:0,marginRight:0,marginTop:0,marginBottom:0,graphs:[{valueField:"value",type:"column",fillAlphas:1,lineColor:"#a9ec49",showBalloon:!1}],valueAxes:[{gridAlpha:0,axisAlpha:0}],categoryAxis:{gridAlpha:0,axisAlpha:0}};$("#reportrange").daterangepicker({locale:{format:"YYYY-MM-DD",separator:" - ",applyLabel:"적용",cancelLabel:"취소",fromLabel:"시작일자",toLabel:"끝일자",customRangeLabel:"범위선택",weekLabel:"W",daysOfWeek:["일","월","화","수","목","금","토"],monthNames:["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"],firstDay:1},alwaysShowCalendars:!0,opens:"right",startDate:mnp_dashboard.start_date,endDate:mnp_dashboard.end_date,maxDate:moment(),ranges:{"지난 30일":[moment().subtract(29,"days"),moment()],"지난 90일":[moment().subtract(89,"days"),moment()],"지난 180일":[moment().subtract(179,"days"),moment()],"이번달":[moment().startOf("month"),moment().endOf("month")],"지난달":[moment().subtract(1,"month").startOf("month"),moment().subtract(1,"month").endOf("month")]}}),$("#reportrange").on("apply.daterangepicker",function(a,b){var c=$("#reportrange").data("daterangepicker").startDate.format("YYYY-MM-DD"),d=$("#reportrange").data("daterangepicker").endDate.format("YYYY-MM-DD");$("#reportrange span").html(c+" - "+d),get_dashboard_data(c,d)}.bind(this)),get_dashboard_data(mnp_dashboard.start_date,mnp_dashboard.end_date),$(".search-interval").on("click",function(){var a=$(this).data("interval");$(".search-interval").removeClass("selected"),$(".search-interval[data-interval="+a+"]").addClass("selected"),$("#reportrange").trigger("apply.daterangepicker")})});