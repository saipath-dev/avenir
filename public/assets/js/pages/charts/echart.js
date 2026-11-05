// Bar Area
$(function () {
  "use strict";
  var barArea = getChart("echart-bar_area");
  var app = {};
  var option = {};
  var xAxisData = [];
  var data1 = [];
  var data2 = [];
  for (var i = 0; i < 100; i++) {
    xAxisData.push("bar" + i);
    data1.push((Math.sin(i / 5) * (i / 5 - 10) + i / 6) * 5);
    data2.push((Math.cos(i / 5) * (i / 5 - 10) + i / 6) * 5);
  }
  option = {
    legend: {
      data: ["bar", "bar2"],
      align: "right",
      bottom: "0",
    },
    grid: {
      left: "5%",
      right: "0%",
      top: "2%",
      bottom: "15%",
    },
    tooltip: {},
    xAxis: {
      data: xAxisData,
      silent: true,
      splitLine: {
        show: false,
      },
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    yAxis: {
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    series: [
      {
        name: "bar",
        type: "bar",
        data: data1,
        color: "#a27ce6",
        animationDelay: function (idx) {
          return idx * 10;
        },
      },
      {
        name: "bar2",
        type: "bar",
        data: data2,
        color: "#3eacff",

        animationDelay: function (idx) {
          return idx * 5 + 100;
        },
      },
    ],
    animationEasing: "elasticOut",
    animationDelayUpdate: function (idx) {
      return idx * 5;
    },
  };
  if (option && typeof option === "object") {
    barArea.setOption(option, true);
  }
  $(window).on("resize", function () {
    barArea.resize();
  });
});

// Rainfall and Evaporation
$(function () {
  "use strict";
  var app = {};
  var option = {};
  var rainFall = getChart("echart-rainfall");
  option = {
    legend: {
      data: ["data1", "data2"],
      bottom: "0",
    },
    grid: {
      left: "5%",
      right: "0%",
      top: "2%",
      bottom: "15%",
    },
    tooltip: {
      trigger: "axis",
    },
    calculable: true,

    xAxis: {
      type: "category",
      data: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "July",
        "Aug",
        "Sept",
        "Oct",
        "Nov",
        "Dec",
      ],
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    yAxis: {
      type: "value",
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    series: [
      {
        name: "data1",
        type: "bar",
        color: "#2bcbba",
        data: [
          2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3,
        ],
        markPoint: {
          data: [
            { type: "max", name: "Max" },
            { type: "min", name: "Min" },
          ],
        },
        markLine: {
          data: [{ type: "average", name: "Average" }],
        },
      },
      {
        name: "data2",
        type: "bar",
        color: "#288cff",
        data: [
          2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3,
        ],
        markPoint: {
          data: [
            { name: "Highest", value: 182.2, xAxis: 7, yAxis: 183 },
            { name: "Minimum", value: 2.3, xAxis: 11, yAxis: 3 },
          ],
        },
        markLine: {
          data: [{ type: "average", name: "Average" }],
        },
      },
    ],
  };
  if (option && typeof option === "object") {
    rainFall.setOption(option, true);
  }
  $(window).on("resize", function () {
    rainFall.resize();
  });
});

// Dynamic Data
$(function () {
  "use strict";
  var dynamicData = getChart("echart-dynamic_data");
  var app = {};
  var option = {};

  option = {
    legend: {
      data: ["Latest transaction price", "Pre-order queue"],
    },
    tooltip: {
      trigger: "axis",
      axisPointer: {
        type: "cross",
        label: {
          backgroundColor: "#283b56",
        },
      },
    },
    dataZoom: {
      show: false,
      start: 0,
      end: 100,
    },
    xAxis: [
      {
        type: "category",
        boundaryGap: true,
        data: (function () {
          var now = new Date();
          var res = [];
          var len = 10;
          while (len--) {
            res.unshift(now.toLocaleTimeString().replace(/^\D*/, ""));
            now = new Date(now - 2000);
          }
          return res;
        })(),
        axisLine: {
          lineStyle: {
            color: "#EEEEEE",
          },
        },
      },
      {
        type: "category",
        boundaryGap: true,
        data: (function () {
          var res = [];
          var len = 10;
          while (len--) {
            res.push(10 - len - 1);
          }
          return res;
        })(),
      },
    ],
    yAxis: [
      {
        type: "value",
        scale: true,
        name: "price",
        max: 30,
        min: 0,
        boundaryGap: [0.2, 0.2],

        axisLine: {
          lineStyle: {
            color: "#EEEEEE",
          },
        },
        axisLabel: {
          color: "#666666",
        },
      },
      {
        type: "value",
        scale: true,
        max: 1200,
        min: 0,
        boundaryGap: [0.2, 0.2],

        splitLine: {
          lineStyle: {
            color: "#EEEEEE",
          },
        },
      },
    ],
    series: [
      {
        color: "#5b39b1",
        name: "queue",
        type: "bar",
        xAxisIndex: 1,
        yAxisIndex: 1,
        data: (function () {
          var res = [];
          var len = 10;
          while (len--) {
            res.push(Math.round(Math.random() * 1000));
          }
          return res;
        })(),
      },
      {
        color: "#2bcbba",
        name: "Latest transaction",
        type: "line",
        data: (function () {
          var res = [];
          var len = 0;
          while (len < 10) {
            res.push((Math.random() * 10 + 5).toFixed(1) - 0);
            len++;
          }
          return res;
        })(),
      },
    ],
  };
  if (option && typeof option === "object") {
    dynamicData.setOption(option, true);
  }
  $(window).on("resize", function () {
    dynamicData.resize();
  });
});

// Basic Candlestick
$(function () {
  "use strict";
  var candleStick = getChart("echart-candlestick");
  var app = {};
  var option = {};

  option = {
    grid: {
      left: "5%",
      right: "0%",
      top: "2%",
      bottom: "8%",
    },
    xAxis: {
      data: ["2018-10-24", "2018-10-25", "2018-10-26", "2018-10-27"],
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
    },
    yAxis: {
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    series: [
      {
        type: "k",
        data: [
          {
            itemStyle: {
              color: "#3eacff",
              borderColor: "#3eacff",
            },
            value: [20, 30, 10, 35],
          },
          {
            itemStyle: {
              color: "#01b2c6",
              color0: "#01b2c6",
              borderColor: "#01b2c6",
              borderColor0: "#01b2c6",
            },
            value: [40, 35, 30, 55],
          },
          {
            itemStyle: {
              color: "#e47297",
              borderColor: "#e47297",
            },
            value: [33, 38, 33, 40],
          },
          {
            itemStyle: {
              color: "#ffce4b",
              borderColor: "#ffce4b",
            },
            value: [40, 40, 32, 42],
          },
        ],
      },
    ],
  };

  if (option && typeof option === "object") {
    candleStick.setOption(option, true);
  }
  $(window).on("resize", function () {
    candleStick.resize();
  });
});

// Basic Scatter Chart
$(function () {
  "use strict";
  var basicScatter = getChart("echart-basic_scatter");
  var app = {};
  var option = {};

  option = {
    grid: {
      left: "5%",
      right: "0%",
      top: "2%",
      bottom: "5%",
    },

    xAxis: {
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    yAxis: {
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    series: [
      {
        symbolSize: 15,
        color: "#ffce4b",
        data: [
          [10.0, 8.04],
          [8.0, 6.95],
          [13.0, 7.58],
          [9.0, 8.81],
          [11.0, 8.33],
          [14.0, 9.96],
          [6.0, 7.24],
          [4.0, 4.26],
          [12.0, 10.84],
          [7.0, 4.82],
          [5.0, 5.68],
        ],
        type: "scatter",
      },
    ],
  };
  if (option && typeof option === "object") {
    basicScatter.setOption(option, true);
  }
  $(window).on("resize", function () {
    basicScatter.resize();
  });
});

// Doughnut Chart
$(function () {
  "use strict";

  var base_url = window.location.origin + "/avenir/";
  // get the first date of this year
  var date = new Date();
  var firstDay = new Date(date.getFullYear(), 0, 1);
  // add 1 day to the first date of this year
  firstDay.setDate(firstDay.getDate() + 1);
  var firstDayString = firstDay.toISOString().split("T")[0];
  // get the last date of this month
  var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
  // add 1 day to the last date of this month
  lastDay.setDate(lastDay.getDate() + 1);
  var lastDayString = lastDay.toISOString().split("T")[0];

  // get_liabilities
  $.ajax({
    url:
      base_url +
      "dashboard/get_liabilities/" +
      firstDayString +
      "/" +
      lastDayString,
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response) {
        $("#total_liability").text(
          parseFloat(response.total_payable).toFixed(2)
        );

        if (response.total_payable > 0) {
          var suppliers = [];
          var liabilities = [];
          if (response.suppliers.length > 5) {
            for (let i = 0; i < 5; i++) {
              var color = "#93c7ce";
              if (i == 0) {
                color = "#08808e";
              } else if (i == 1) {
                color = "#149faf";
              } else if (i == 2) {
                color = "#2bb7c7";
              } else if (i == 3) {
                color = "#55bfcc";
              }

              if (i < 4) {
                suppliers.push(response.suppliers[i].Name);
                liabilities.push({
                  value: response.suppliers[i].balance,
                  name: response.suppliers[i].Name,
                  itemStyle: { color: color },
                });
              } else {
                // all other suppliers
                var balance = 0;
                for (let j = 4; j < response.suppliers.length; j++) {
                  balance += response.suppliers[j].balance;
                }
                suppliers.push("Others");
                liabilities.push({
                  value: balance,
                  name: "Others",
                  itemStyle: { color: "#93c7ce" },
                });
              }
            }
          } else {
            for (let i = 0; i < response.suppliers.length; i++) {
              suppliers.push(response.suppliers[i].Name);
            }
          }

          var doughnutChart = getChart("echart-doughnut");
          var app = {};
          var option = {};

          option = {
            grid: {
              left: "5%",
              right: "0%",
              top: "2%",
              bottom: "5%",
            },

            legend: {
              orient: "vertical",
              x: "left",
              data: suppliers,
            },
            series: [
              {
                name: "Access source",
                type: "pie",
                radius: ["50%", "70%"],
                avoidLabelOverlap: false,
                label: {
                  normal: {
                    show: false,
                    position: "center",
                  },
                  emphasis: {
                    show: true,
                    textStyle: {
                      fontSize: "12",
                      fontWeight: "bold",
                    },
                  },
                },
                labelLine: {
                  normal: {
                    show: false,
                  },
                },
                data: liabilities,
              },
            ],
          };
          if (option && typeof option === "object") {
            doughnutChart.setOption(option, true);
          }
          $(window).on("resize", function () {
            doughnutChart.resize();
          });
          
          $("#echart-doughnut").removeClass("d-none");
        }
        else{
          $("#total_liability").text("0.00");
          // hide echart-doughnut d-none
          $("#echart-doughnut").addClass("d-none");
        }
      }
    },
  });
});

// Large scale area chart
$(function () {
  "use strict";

  var largescaleArea = getChart("echart-large_scale_area");
  var app = {};
  var option = {};
  var base = +new Date(1968, 9, 3);
  var oneDay = 24 * 3600 * 1000;
  var date = [];

  var data = [Math.random() * 300];

  for (var i = 1; i < 20000; i++) {
    var now = new Date((base += oneDay));
    date.push([now.getFullYear(), now.getMonth() + 1, now.getDate()].join("/"));
    data.push(Math.round((Math.random() - 0.5) * 20 + data[i - 1]));
  }

  option = {
    tooltip: {
      trigger: "axis",
      position: function (pt) {
        return [pt[0], "10%"];
      },
    },
    grid: {
      left: "5%",
      right: "0%",
      top: "2%",
      bottom: "20%",
    },
    xAxis: {
      type: "category",
      boundaryGap: false,
      data: date,
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    yAxis: {
      type: "value",
      boundaryGap: [0, "100%"],
      splitLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLine: {
        lineStyle: {
          color: "#EEEEEE",
        },
      },
      axisLabel: {
        color: "#666666",
      },
    },
    dataZoom: [
      {
        type: "inside",
        start: 0,
        end: 10,
      },
      {
        start: 0,
        end: 10,
        handleIcon:
          "M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z",
        handleSize: "80%",
        handleStyle: {
          color: "#fff",
          shadowBlur: 3,
          shadowColor: "rgba(0, 0, 0, 0.6)",
          shadowOffsetX: 2,
          shadowOffsetY: 2,
        },
      },
    ],
    series: [
      {
        name: "Simulation data",
        type: "line",
        smooth: true,
        symbol: "none",
        sampling: "average",
        itemStyle: {
          color: "#EEEEEE",
        },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            {
              offset: 0,
              color: "rgb(255, 158, 68)",
            },
            {
              offset: 1,
              color: "rgb(255, 70, 131)",
            },
          ]),
        },
        data: data,
      },
    ],
  };
  if (option && typeof option === "object") {
    largescaleArea.setOption(option, true);
  }
  $(window).on("resize", function () {
    largescaleArea.resize();
  });
});

function getChart(id) {
  var dom = document.getElementById(id);
  // 6 Oct 2026: this file draws the theme's sample charts by element id and is loaded on pages that have only some of
  // them (or none). echarts.init(null) threw "Cannot read properties of null (reading 'getAttribute')" on every such
  // page. A page without the element now gets a chart that does nothing; pages with it are drawn exactly as before.
  if (!dom) {
    return { setOption: function () {}, resize: function () {}, on: function () {}, off: function () {}, dispatchAction: function () {}, clear: function () {} };
  }
  return echarts.init(dom);
}
