<?php

declare(strict_types = 1);

exec('"$(composer config bin-dir)/codecept" --no-interaction build');
