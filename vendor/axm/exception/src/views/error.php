<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Error <?= $data['code'] ?></title>

    <style type="text/css">
        <?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(dirname(__DIR__) . '/assets/debug.css')) ?>
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
                <h1>PHP Error <?= '[' . $data['code'] . ']  - ' . $data['type'] ?></h1>
                <h2><?= nl2br(htmlspecialchars($data['message'] . ' (' . $data['file'] . ':' . $data['line'] . ')', ENT_QUOTES,  APP_CHARSET)) ?></h2>

            </div>
        </header>

        <div class="traces"></div>

        <div class="source">
            <p class="file"><?= htmlspecialchars($file, ENT_QUOTES,  APP_CHARSET) . "({$line})" ?></p>
            <?= self::renderSourceCode($file, $line, self::$maxSourceLines) ?>
        </div>


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


        <div class="version">
            <center><a href='https://www.axmframework.com/'>© 2021 - <?= date('Y') ?> Axm Framework PHP <?= Axm::getVersion() ?></a></center>
        </div>

        <script type="text/javascript">
            <?= file_get_contents(dirname(__DIR__) . '/assets/debug.js') ?>
        </script>
    </div>

</body>

</html>