
Highcharts.setOptions({
    lang: {
        shortMonths: ['Sau', 'Vas', 'Kov', 'Bal', 'Geg', 'Bir', 'Lie', 'Rgp', 'Rgs', 'Spl', 'Lap', 'Grd']
} });


// generating the main chart in the website
(function($) {
	// Pretty tooltips
	$('#change').tooltip();
	
	
	// Chart
	var chart = new Highcharts.Chart({
		chart: {
			renderTo: "chart",
			type: "area",
			backgroundColor: null,
		},
		
		colors: [
			ChartData.color
		],
		
		title: {
			text: null
		},
		
		legend: {
			enabled: false,
		},
		
		credits: {
			text: "Duomenys: SoDra.",
			href: "http://www.sodra.lt/lt/paslaugos/informacijos_rinkmenos/draudeju_duomenys",
			style: {
				color: "#666"
			}
		},
		
		xAxis: {
			type: "datetime",
			lineColor: "#CCC",
			labels: {
				y: -10,
				style: {
					color: "#DDD"
				}
			}
		},
		
		yAxis: {
			title: null,
			gridLineWidth: 0,
			labels: {
				enabled: false
			},
			min: Math.min.apply(null, [ChartData.min])
		},
		
		tooltip: {
			formatter: function() {
				return this.series.name + " " + Highcharts.dateFormat("%b %e", this.x) + ": <b>"+
					Highcharts.numberFormat(this.y, 0) + "</b>";
			}
		},
		
		plotOptions: {
			area: {
				lineWidth: 1,
				marker: {
					enabled: false,
					symbol: "circle",
					radius: 2,
					states: {
						hover: {
							enabled: true
						}
					}
				}
			}
		},
		
		series: [{
			type: 'area',
			name: "Darbuotojų skaičius",
			//pointInterval: 24 * 3600 * 1000,
			//pointStart: ChartData.startingPoint,
			data: ChartData.parsed_data
			}]
		});
})(jQuery);
