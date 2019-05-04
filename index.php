<?php require_once(dirname(__FILE__) . '/frontend/init.php'); ?>
<!DOCTYPE html>
<html lang="lt">
	<head>
		<meta charset="utf-8">
		
		<!-- Metadata -->
		<title></title>
		<base href="" />
		
		<meta name="description" content="">
		<meta name="author" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		<!-- Open graph -->
		<meta property=og:title content="" />
		<meta property=og:type content="website" />
		<meta property=og:url content="" />
		<meta property=og:description content="" />
		<meta property=og:image content="" />
		<meta property=fb:admins content="833735472,516139298" />

		<!-- Styles -->
		<link href="styles/style.css" rel="stylesheet">
		<link href="styles/tooltips.css" rel="stylesheet">
		
		<!-- HTML5 shim (IE6-8 support of HTML5 elements) -->
		<!--[if lt IE 9]>
			<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->

		<!-- Favicons and touch icons -->
		<link rel="shortcut icon" href="ico/favicon.ico">
		<link rel="apple-touch-icon" href="ico/apple-touch-icon.png">
		<link rel="apple-touch-icon" sizes="72x72" href="ico/apple-touch-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="114x114" href="ico/apple-touch-icon-114x114.png">
		
		<!-- Google Analytics -->
		<script type="text/javascript">

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-19315846-16']);
		  _gaq.push(['_setDomainName', 'opendata.lt']);
		  _gaq.push(['_trackPageview']);

		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
	</head>
	
	<body>
		<div id="ticker">
			<div id="count">
				<?php echo number_format(Response::getMe()->getTotalCount(), 0, '', ','); ?>
			</div>
			
			<div id="caption">
				Lietuvos <?php echo Response::getMe()->getEnding('gyventojas', 'gyventojai', 'gyventojų'); ?> šiandien turi darbą
				<a 	href="#" rel="tooltip" title="Darbuotojų skaičiaus pokytis nuo vakar" id="change" 
					class="<?php echo (Response::getMe()->getChange() > 0) ? 'positive' : 'negative'; ?>">
					(<?php echo (Response::getMe()->getChange() > 0) ? '+' : ''; echo Response::getMe()->getChange(); ?>)</a>
			</div>			
			<div id="chart">
			</div>
		</div>
		
		<!-- Scripts -->
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
		<script>
			var ChartData = <?php echo json_encode(Response::getMe()->getChartData()); ?>;
			ChartData.parsed_data = jQuery.map(ChartData.data, parseFloat);
			ChartData.startingPoint = ChartData.startDate * 1000;
			ChartData.parsed_data = [];
			ChartData.min = 999999999999;
			for (var i in ChartData.data) { 
				ChartData.parsed_data.push([parseFloat(i) * 1000, parseFloat(ChartData.data[i])])
				if (ChartData.data[i] < ChartData.min) {
					ChartData.min = ChartData.data[i];
				} 
			}
		</script>
		<script src="js/tooltips.js" type="text/javascript"></script>
		<script src="js/highcharts.js" type="text/javascript"></script>
		<script src="js/scripts.js" type="text/javascript"></script>
	</body>
	
</html>
