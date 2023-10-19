<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Forbidden</title>
	<style type="text/css">
		/*<![CDATA[*/
		body {
			font-family: "Verdana";
			font-weight: normal;
			color: black;
			background-color: white;
		}

		h1 {
			font-family: "Verdana";
			font-weight: normal;
			font-size: 18pt;
			color: red
		}

		h2 {
			font-family: "Verdana";
			font-weight: normal;
			font-size: 14pt;
			color: maroon
		}

		h3 {
			font-family: "Verdana";
			font-weight: bold;
			font-size: 11pt
		}

		p {
			font-family: "Verdana";
			font-weight: normal;
			color: black;
			font-size: 9pt;
			margin-top: -5px
		}

		.version {
			color: gray;
			font-size: 8pt;
			border-top: 1px solid #aaaaaa;
		}

		/*]]>*/
	</style>
</head>

<body>
	<h1>Forbidden</h1>
	<h2><?= nl2br(Html::encode($data['message'])); ?></h2>
	<p>
		You do not have the proper credential to access this page.
	</p>
	<p>
		If you think this is a server error, please contact <?= $data['admin']; ?>.
	</p>
	<div class="version">
		<?= date('Y-m-d H:i:s', $data['time']) . ' ' . $data['version']; ?>
	</div>
</body>

</html>