<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= APP_CHARSET ?>" />
	<title>Oh! Hay un error - Axm Framework PHP</title>

	<style type="text/css">
		<?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'assets/debug.css')) ?>
	</style>

	<style type="text/css">
		/*<![CDATA[*/
		html,
		body,
		div,
		span,
		applet,
		object,
		iframe,
		h1,
		h2,
		h3,
		h4,
		h5,
		h6,
		p,
		blockquote,
		pre,
		a,
		abbr,
		acronym,
		address,
		big,
		cite,
		code,
		del,
		dfn,
		em,
		font,
		img,
		ins,
		kbd,
		q,
		s,
		samp,
		small,
		strike,
		strong,
		sub,
		tt,
		var,
		b,
		u,
		i,
		center,
		dl,
		dt,
		dd,
		ol,
		ul,
		li,
		fieldset,
		form,
		label,
		legend,
		table,
		caption,
		tbody,
		tfoot,
		thead,
		tr,
		th,
		td {
			border: 0;
			outline: 0;
			font-size: 100%;
			vertical-align: baseline;
			background: transparent;
			margin: 0;
			padding: 0;
		}

		body {
			line-height: 1;
		}

		ol,
		ul {
			list-style: none;
		}

		blockquote,
		q {
			quotes: none;
		}

		blockquote:before,
		blockquote:after,
		q:before,
		q:after {
			content: none;
		}

		:focus {
			outline: 0;
		}

		ins {
			text-decoration: none;
		}

		del {
			text-decoration: line-through;
		}

		table {
			border-collapse: collapse;
			border-spacing: 0;
		}

		body {
			font: normal 9pt "Verdana";
			color: #000;
			background: #fff;
		}

		h1 {
			font: normal 19pt "Verdana";
			color: #f00;
			margin-bottom: .5em;
			margin-top: .7em;
			margin-left: .5em;


		}

		h2 {
			font: normal 14pt "Verdana";
			color: #800000;
			margin-bottom: .5em;
		}

		h3 {
			font: bold 11pt "Verdana";
		}

		pre {
			font: normal 11pt Menlo, Consolas, "Lucida Console", Monospace;
		}

		pre span.error {
			display: block;
			background: #fce3e3;
		}

		pre span.ln {
			color: #999;
			padding-right: 0.5em;
			border-right: 1px solid #ccc;
		}

		pre span.ln:hover {
			background: var(--light-bg-color);
			border-color: rgba(0, 0, 0, 0.15);
		}


		pre span.error-ln {
			font-weight: bold;
		}


		.container {
			margin: 1em 4em;
		}


		.message {
			color: #000;
			padding: 1em;
			font-size: 11pt;
			background: #f3f3f3;
			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
			border-radius: 10px;
			margin-bottom: 1em;
			margin-left: .1em;
			margin-top: .5em;

			line-height: 160%;
		}

		.solution {
			color: #000;
			padding: 1em;
			font-size: 11pt;
			background: #d8ffd8;
			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
			border-radius: 10px;
			margin-bottom: 1em;
			margin-left: .1em;
			margin-top: .5em;

			line-height: 160%;
		}

		.source {
			margin-bottom: 1em;
		}


		.code pre {
			/* #ffe */
			/**#fafbfd;
				#343434  */
			background-color: #fafbfd;
			margin: 0.6em -0.1% 0 0;
			padding: 0.5em;
			line-height: 125%;
			border: 1px solid #eee;
			overflow-x: auto;
		}

		.code span:hover {
			background: #f1f1f1;
		}


		.source .file {
			margin-bottom: 1em;
			font-weight: bold;

		}


		.traces {
			margin: 2em 0;
		}

		.trace {
			margin: 0.5em 0;
			padding: 0.5em;
		}

		.trace.app {
			border: 1px dashed #fafbfd;
		}

		.trace .number {
			text-align: right;
			width: 1em;
			padding: 0.5em;
		}

		.trace .content {
			padding: 0.5em;
		}

		.trace .plus,
		.trace .minus {
			display: inline;
			vertical-align: middle;
			text-align: center;
			border: 1px solid #000;
			color: #000;
			font-size: 10px;
			line-height: 10px;
			margin: 0;
			padding: 0 1px;
			width: 10px;
			height: 10px;
		}

		.trace.collapsed .minus,
		.trace.expanded .plus,
		.trace.collapsed pre {
			display: none;
		}

		.trace-file {
			cursor: pointer;
			padding: 0.2em;
		}

		.trace-file:hover {
			background: #f0ffff;
		}



		:root {
			--main-bg-color: #fff;
			--main-text-color: #555;
			--dark-text-color: #222;
			--light-text-color: #c7c7c7;
			--brand-primary-color: #E06E3F;
			--light-bg-color: #ededee;
			--dark-bg-color: #404040;
		}

		h1.headline {
			margin-top: 20%;
			font-size: 5rem;
		}

		.text-center {
			text-align: center;
		}

		p.lead {
			font-size: 1.6rem;
		}

		.container {
			max-width: 75rem;
			margin: 0 auto;
			padding: 1rem;
		}

		.header {
			background: var(--light-bg-color);
			color: var(--dark-text-color);
		}

		.header .container {
			padding: 1rem 1.75rem 1.75rem 1.75rem;
		}

		.header h1 {
			font-size: 2.5rem;
			font-weight: 500;
		}

		.header p {
			font-size: 1.2rem;
			margin: 0;
			line-height: 2.5;
		}

		.header a {
			color: var(--brand-primary-color);
			margin-left: 2rem;
			display: none;
			text-decoration: none;
		}

		.header:hover a {
			display: inline;
		}

		.footer {
			background: var(--dark-bg-color);
			color: var(--light-text-color);
		}

		.footer .container {
			border-top: 1px solid #e7e7e7;
			margin-top: 1rem;
			text-align: center;
		}

		/* .row {
			display: flex;
		}

		.copy-icons {
			display: flex;

		} */

		.row {
			display: flex;

		}


		.source {
			background: #343434;
			color: var(--light-text-color);
			padding: 0.5em 1em;
			border-radius: 5px;
			font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
			font-size: 1rem;
			margin: 0;
			/* overflow-x: scroll; */
		}

		.source span.line {
			line-height: 1.4;
		}

		.source span.line .number {
			color: #666;
		}

		.source .line .highlight {
			display: block;
			background: var(--dark-text-color);
			color: var(--light-text-color);
		}

		.source span.highlight .number {
			color: #fff;
		}

		.content {
			padding: 1rem;
		}

		.hide {
			display: none;
		}

		.alert {
			margin-top: 2rem;
			display: block;
			text-align: center;
			line-height: 3.0;
			background: #d9edf7;
			border: 1px solid #bcdff1;
			border-radius: 5px;
			color: #31708f;
		}

		ul,
		ol {
			line-height: 1.8;
		}

		table {
			width: 100%;
			overflow: hidden;
		}

		th {
			text-align: left;
			border-bottom: 1px solid #e7e7e7;
			padding-bottom: 0.5rem;
		}

		td {
			padding: 0.2rem 0.5rem 0.2rem 0;
		}

		tr:hover td {
			background: #f1f1f1;
		}

		td pre {
			white-space: pre-wrap;
		}

		.trace a {
			color: inherit;
		}

		.trace table {
			width: auto;
		}

		/* .trace tr td:first-child {
				min-width: 5em;
				font-weight: bold;
			} */
		.trace td {
			background: var(--light-bg-color);
			padding: 0 1rem;
		}

		.trace td pre {
			margin: 0;
		}

		.args {
			display: none;
		}

		/*]]>*/
	</style>

</head>

<body>
	<div class="container">
		<header class="row">

			<svg xmlns="http://www.w3.org/2000/svg" width="25mm" height="12.5mm" viewBox="0 0 190.808 100.378">
				<path d="M92.3205 90.9973c-.445122-1.32736-3.81609-21.7742-3.81609-23.1467 0-.57608 1.12455-.90324 2.49901-.72703 1.96237.25159 2.5696-.23783 2.82776-2.27908.304034-2.40397.721691-2.58506 5.55335-2.40785 2.87353.10539 5.28707.69911 5.36342 1.31939.0763.62028.20131 1.5886.27767 2.15183.0763.56324 1.13844 1.02407 2.36017 1.02407 1.43761 0 2.22134.6827 2.22134 1.93499 0 1.69759-2.51239 15.4152-3.86564 21.1064-.41717 1.75441-1.38979 2.04811-6.78229 2.04811-3.4624 0-6.44982-.46081-6.63869-1.02405Z" style="fill:#e91d04;stroke-width:1.06649" transform="translate(-8.76377 -62.38646)" />
				<path d="M8.82635 161.529c12.91185-30.262 18.05435-41.775 18.65905-41.775.41143 0 2.28543 3.80182 4.16445 8.4485l3.41639 8.4485-4.78359 12.8007-4.78359 12.8008-8.61909.30138c-6.70031.23428-8.4932.007-8.05358-1.02406Zm41.0068-1.32545c-1.18625-2.40052-1.78603-2.56015-9.61897-2.56015-4.59461 0-8.35383-.39206-8.35383-.87124 0-.47917.548694-2.32249 1.21932-4.09625 1.11862-2.95863 1.62314-3.22499 6.10868-3.22499 3.2839 0 4.88936-.43934 4.88936-1.33798 0-.73588-2.99881-8.19827-6.66402-16.5831-3.66521-8.38479-6.66402-15.515-6.66402-15.8449 0-.80794 15.9213-.0157 16.7913.83628.704377.68922 20.9715 44.9236 20.9715 45.7718 0 .2589-3.91818.47073-8.70708.47073-8.20973 0-8.77934-.1462-9.97221-2.56017Zm21.6901 1.28009c.5838-.70404 4.19861-5.30817 8.03293-10.2314 3.83431-4.92323 7.34566-8.73809 7.803-8.47748.457353.26062 2.69577 2.96955 4.97428 6.01986l4.14273 5.54601-3.02276 4.21154-3.02276 4.21153h-9.98443c-7.8132 0-9.7536-.27839-8.92297-1.28008ZM89.6842 139.239c-9.8119-12.939-17.84-23.653-17.8402-23.81-.0003-.156 4.1231-.213 9.163-.126l9.16346.15754 3.98305 4.96273c4.48017 5.58212 30.0998 39.0817 31.4024 41.0609.64797.98456-1.33613 1.28008-8.59472 1.28008h-9.43716Zm42.6917 3.08067c0-14.7678.3343-20.0994 1.20395-19.2011.66214.68393 4.28571 5.74012 8.05235 11.236l6.8484 9.99248v18.41731h-16.1047Zm49.682-3.29961.29813-23.7443 7.7747.14341c4.27606.0788 8.14953.16469 8.60767.19084.45815.0262.83301 10.6465.83301 23.6008v23.5534h-17.81165Zm-32.8687.26969c-8.33102-12.3478-15.1477-22.8316-15.1483-23.2974-.001-.89893 15.9601-.85514 17.5452.0481.51364.29269 3.89847 4.92938 7.52184 10.3038 3.62337 5.3744 7.08775 9.62958 7.69861 9.45597.61089-.17358 3.35978-3.70496 6.1087-7.84747l4.99801-7.53181.31614 11.0134.31615 11.0134-6.38436 9.64625c-3.51141 5.30544-6.70845 9.64624-7.10455 9.64624-.3961 0-7.53647-10.1027-15.8675-22.4505Zm-43.4025-9.47892-4.29457-5.30385 2.54583-4.42473 2.54583-4.42473h9.3789c5.15841 0 9.21233.36436 9.0087.8097-.94224 2.06072-13.5585 18.6474-14.1838 18.6474-.38851 0-2.63892-2.38674-5.00094-5.30384ZM73.5297 108.745c7.485-10.8614 20.6458-15.8117 33.3307-12.537 6.75834 1.74467 20.2131 12.9031 18.0089 14.9353-.25697.23692-6.28387-2.09891-13.3931-5.19076l-12.9259-5.62155-12.9117 5.61509c-7.10141 3.08829-13.1677 5.61509-13.4807 5.61509-.312953 0 .304325-1.26727 1.37173-2.81617Z" style="fill:#010101;stroke-width:1.06649" transform="translate(-8.76377 -62.38646)" />
			</svg>

			<div>
				<h1><?= 'Type: ' . $type ?></h1>

				<div>
					<span class="copy-icons">
						<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
							<path d="M0 0h24v24H0z" fill="none" />
							<path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
						</svg>
					</span>

				</div>

			</div>

		</header>

		<p class="message">
			<?= nl2br($message) ?>
		</p>



		<div class="source">
			<p class="file"><?= htmlspecialchars($file, ENT_QUOTES,  APP_CHARSET) . "({$line})" ?></p>
			<?= self::renderSourceCode($file, $line, self::$maxSourceLines) ?>
		</div>

		<div class="traces"></div>


		<!-- tab links -->
		<div class="tab">
			<button class="tablinks" onclick="openTab(event,'Rastro')" id="default">Rastro</button>
			<button class="tablinks" onclick="openTab(event,'Server')">Server</button>
			<button class="tablinks" onclick="openTab(event,'Request')">Request</button>
			<button class="tablinks" onclick="openTab(event,'Response')">Response</button>
			<button class="tablinks" onclick="openTab(event,'Files')">Files</button>
			<button class="tablinks" onclick="openTab(event,'Session')">Session</button>
			<button class="tablinks" onclick="openTab(event,'Constants')">Constants</button>
			<button class="tablinks" onclick="openTab(event,'Cookie')">Cookie</button>
			<button class="tablinks" onclick="openTab(event,'Info')">Info</button>
		</div>


		<!-- tab Rastro -->
		<div class="tab-content" id="Rastro">
			<h2>Rastro</h2>
			<!-- Rastro -->
			<div class="traces">
				<table style="width:100%;">
					<?php
					$count = 0;
					foreach ($traces as $i => $trace) : ?>
						<?php
						if (self::isCoreCode($trace))
							$cssClass = 'core collapsed';
						elseif (++$count > 3)
							$cssClass = 'app collapsed';
						else
							$cssClass = 'app expanded';
						$hasCode = isset($trace['file']) && $trace['file'] !== 'unknown' && is_file($trace['file']);
						?>
						<tr class="trace <?= $cssClass ?>">
							<td class="number">
								#<?= $i ?>
							</td>
							<td class="content">
								<div class="trace-file">
									<?php if ($hasCode) : ?>
										<div class="plus">+</div>
										<div class="minus">–</div>
									<?php endif ?>
									<?php
									echo '&nbsp;';
									echo htmlspecialchars($trace['file'] ??= 'unknown', ENT_QUOTES, APP_CHARSET) . "(" . $trace['line'] ??= '0' . ")";
									echo ': ';
									if (!empty($trace['class']))
										echo "<strong>{$trace['class']}</strong>{$trace['type']}";
									echo "<strong>{$trace['function']}</strong>(";
									if (!empty($trace['args']))
										echo htmlspecialchars(self::argumentsToString($trace['args']), ENT_QUOTES, APP_CHARSET);
									echo ')';
									?>
								</div>

								<?php if ($hasCode) echo self::renderSourceCode($trace['file'], $trace['line'], self::$maxTraceSourceLines) ?>
							</td>
						</tr>
					<?php endforeach ?>

				</table>
			</div>
		</div>




		<!-- tab Server -->
		<div class="tab-content" id="Server">
			<h2>$_SERVER</h2>
			<!-- Server -->
			<div class="content" id="server">
				<table>
					<thead>
						<tr>
							<th>Key</th>
							<th>Value</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($GLOBALS['_SERVER'] as $key => $value) : ?>
							<tr>
								<td><?= $key ?></td>
								<td>
									<?php if (is_string($value)) : ?>
										<?= ($value) ?>
									<?php else : ?>
										<pre><?= print_r($value, true) ?></pre>
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>


		<!-- tab Request -->
		<div class="tab-content" id="Request">
			<h2>Request</h2>
			<!-- Request -->
			<div class="content" id="request">
				<?php $request = Axm::app()->request ?>

				<table>
					<tbody>
						<tr>
							<td style="width: 10em">Path</td>
							<td><?= $request->getUri() ?></td>
						</tr>
						<tr>
							<td>HTTP Method</td>
							<td><?= $request->getMethod() ?></td>
						</tr>
						<tr>
							<td>IP Address</td>
							<td><?= $request->getIPAddress() ?></td>
						</tr>
						<tr>
							<td style="width: 10em">Is AJAX Request?</td>
							<td><?= $request->isAjax() ? 'yes' : 'no' ?></td>
						</tr>
						<tr>
							<td>Is CLI Request?</td>
							<td><?= $request->isCLI() ? 'yes' : 'no' ?></td>
						</tr>
						<tr>
							<td>Is Secure Request?</td>
							<td><?= $request->isSecure() ? 'yes' : 'no' ?></td>
						</tr>
						<tr>
							<td>User Agent</td>
							<td><?= $request->getUserAgent() ?></td>
						</tr>

					</tbody>
				</table>

			</div>
		</div>


		<!-- tab Response -->
		<div class="tab-content" id="Response">
			<h2>Response</h2>
			<!-- Response -->
			<?php
			$response = Axm::app()->response;
			$response->setStatusCode(http_response_code());
			?>
			<div class="content" id="response">
				<table>
					<tr>
						<td style="width: 15em">Response Status</td>
						<td><?= $response->getStatusCode() ?></td>
					</tr>
				</table>
			</div>
		</div>


		<!-- tab Files -->
		<div class="tab-content" id="Files">
			<h2>Files</h2>
			<!-- Files -->
			<div class="content" id="files">
				<?php

				$files = get_included_files();
				$coreFiles = [];
				$userFiles = [];

				foreach ($files as $path) {

					if (strpos($path, AXM_PATH) !== false) {
						$coreFiles[] = $path;
					} else {
						$userFiles[] = $path;
					}
				} ?>

				<li>Included Files:
					<b><?= count($files) ?></b>
				</li>
				<br>
				<li>Included Core Files:
					<b><?= count($coreFiles) ?></b>
				</li>
				<br>
				<li>Included User Files:
					<b><?= count($userFiles) ?></b>
				</li>

				<br>
				<br>
				<br>

				<table>
					<thead>
						<tr>
							<th>Name</th>
							<th>Path</th>
							<th>Type</th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ($files as $file) : ?>
							<tr>
								<td>
									<?= basename($file) ?>
								</td>
								<td>
									<?= dirname($file) ?>
								</td>
								<td>
									<?= (in_array($file, $coreFiles)) ? 'Core' : 'User' ?>
								</td>

							</tr>
						<?php endforeach ?>
					</tbody>

				</table>

			</div>
		</div>



		<div class="tab-content" id="Session">
			<h2>Session</h2>

			<table>
				<thead>
					<tr>
						<th>Key</th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($GLOBALS['_SESSION'] ?? [] as $key => $value) : ?>
						<tr>
							<td><?= $key ?></td>
							<td>
								<?php if (is_string($value)) : ?>
									<?= ($value) ?>
								<?php else : ?>
									<pre><?= print_r($value, true) ?></pre>
								<?php endif ?>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>

		</div>


		<!-- tab Constants -->
		<div class="tab-content" id="Constants">
			<h2>Constants</h2>
			<!-- Constants -->
			<?php $constants = get_defined_constants(true); ?>
			<?php if (!empty($constants)) : ?>

				<table>
					<thead>
						<tr>
							<th>Key</th>
							<th>Value</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($constants as $key => $value) : ?>
							<tr>
								<td><?= $key ?></td>
								<td>
									<?php if (is_string($value)) : ?>
										<?= $value ?>
									<?php else : ?>
										<pre><?= print_r($value, true) ?></pre>
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			<?php endif ?>
		</div>


		<!-- tab Cookie -->
		<div class="tab-content" id="Cookie">
			<h2>Cookie</h2>
			<table>
				<thead>
					<tr>
						<th>Key</th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($GLOBALS['_COOKIE'] as $key => $value) : ?>
						<tr>
							<td><?= $key ?></td>
							<td>
								<?php if (is_string($value)) : ?>
									<?= ($value) ?>
								<?php else : ?>
									<pre><?= print_r($value, true) ?></pre>
								<?php endif ?>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>

		<!-- tab Info -->
		<div class="tab-content" id="Info">
			<h2>Info</h2>
			<table>
				<tbody>
					<tr>
						<td>Memory Usage</td>
						<td><?= number_format(memory_get_usage() / 1048576, 3), ' MB' ?></td>
					</tr>
					<tr>
						<td style="width: 12em">Peak Memory Usage:</td>
						<td><?= number_format(memory_get_peak_usage() / 1048576, 3), ' MB' ?></td>
					</tr>
					<tr>
						<td>Memory Limit:</td>
						<td><?= ini_get('memory_limit') ?></td>
					</tr>
					<tr>
						<td>Versión PHP:</td>
						<td><?= PHP_VERSION ?></td>
					</tr>

					<tr>
						<td>Versión Axm:</td>
						<td><?=Axm::getVersion() ?></td>
					</tr>


				</tbody>
			</table>

		</div>



		<div class="traces"></div>
		<div class="traces"></div>


		<div class="version">
			<center><a href='https://www.axmframework.com/'>© 2021 - <?= date('Y') ?> Axm Framework PHP <?= Axm::getVersion() ?></a></center>
		</div>
	</div>

	<script type="text/javascript">
		<?= file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'assets/debug.js') ?>
	</script>


	<script type="text/javascript">
		/*<![CDATA[*/
		var traceReg = new RegExp("(^|\\s)trace-file(\\s|$)");
		var collapsedReg = new RegExp("(^|\\s)collapsed(\\s|$)");

		var e = document.getElementsByTagName("div");
		for (var j = 0, len = e.length; j < len; j++) {
			if (traceReg.test(e[j].className)) {
				e[j].onclick = function() {
					var trace = this.parentNode.parentNode;
					if (collapsedReg.test(trace.className))
						trace.className = trace.className.replace("collapsed", "expanded");
					else
						trace.className = trace.className.replace("expanded", "collapsed");
				}
			}
		}

		/*]]>*/
	</script>

</body>

</html>