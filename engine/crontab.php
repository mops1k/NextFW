<?php
namespace NextFW\Engine;

use NextFW\Config as Config;

/**
 * Class Crontab
 * @package NextFW\Engine
 */
class Crontab
{
    const CRON_COMMENT = 0;
    const CRON_ASSIGN = 1;
    const CRON_CMD = 2;
    const CRON_SPECIAL = 3;
    const CRON_EMPTY = 4;
    const CRON_EMPTYLINE = 5;
    /*
       $crontabs: Array that holds all the different lines. Lines are associative arrays with the following fields:
        "minute" : holds the minutes (0-59)
        "hour"	: holds the hour (0-23)
        "dayofmonth": holds the day of the month (1-31)
        "month" : the month (1-12 or the names)
        "dayofweek" : 0-7 (or the names)

        or a line can be a 2-value array that represents an assignment: "name" => "value"
        or a line can be a comment (string beginning with #)
        or it can be a special command (beginning with an @)
    */
	/**
	 * @var array $crontabs
     */
	public $crontabs;
    private $user; // the user for whom the crontab will be manipulated
    private $linetypes; // Lists the type of line of each line in $crontabs. can be: any of the CRON_* constants. so $linetype[5] is the type of $crontabs[5].


    /** Methods */

	/**
	 * The constructor. Initialises $this->crontabs
	 * @param string $user System username for crontab. Example: www-data
     */
	function __construct($user = NULL)
    {
        $this->user = $user == NULL ? get_current_user() : $user;
        $this->readCrontab();
    }

	/**
	 * This reads the crontab of $this->user and parses it in $this->crontabs
     */
	function readCrontab()
    {
        exec("crontab -u $this->user -l", $crons, $return);

        foreach ($crons as $line) {
            $line = trim($line); // discarding all prepending spaces and tabs

            // empty lines..
            if (!$line) {
                $this->crontabs[] = "empty line";
                $this->linetypes[] = self::CRON_EMPTY;
                continue;
            }

            // checking if this is a comment
            if ($line[0] == "#") {
                $this->crontabs[] = trim($line);
                $this->linetypes[] = self::CRON_COMMENT;
                continue;
            }

            // Checking if this is an assignment
            if (preg_match("/(.*)=(.*)/i", $line, $assign)) {
                $this->crontabs[] = ["name" => $assign[1], "value" => $assign[2]];
                $this->linetypes[] = self::CRON_ASSIGN;
                continue;
            }

            // Checking if this is a special @-entry. check man 5 crontab for more info
            if ($line[0] == '@') {
                $this->crontabs[] = explode("[ \t]", $line, 2);
                $this->linetypes[] = self::CRON_SPECIAL;
                continue;
            }

            // It's a regular crontab-entry
            $ct = preg_split("/[ \t]/si", $line, 6);
            $this->addCron($ct[0], $ct[1], $ct[2], $ct[3], $ct[4], $ct[5]);
        }
    }

	/**
	 * Writes the current crontab
	 * @throws \Exception
     */
	function writeCrontab()
    {
        $filename = PATH . "tmp/crontab_" . time();
        $file = fopen($filename, "w");

        for ($i = 0; $i < count($this->linetypes); $i++) {
            switch ($this->linetypes[ $i ]) {
                case self::CRON_COMMENT :
                    $line = $this->crontabs[ $i ];
                    break;
                case self::CRON_ASSIGN:
                    $line = $this->crontabs[ $i ]['name'] . " = " . $this->crontabs[ $i ]['value'];
                    break;
                case self::CRON_CMD:
                    $line = implode(" ", $this->crontabs[ $i ]);
                    break;
                case self::CRON_SPECIAL:
                    $line = implode(" ", $this->crontabs[ $i ]);
                    break;
                case self::CRON_EMPTYLINE:
                    $line = "\n"; // an empty line in the crontab-file
                    break;
                default:
                    unset($line);
					throw new \Exception("Something very weird is going on. This line ($i) has an unknown type.");
                    break;
            }

            // echo "line $i : $line\n";

            if ($line) {
                fwrite($file, $line . "\n");
            }
        }
        fclose($file);


        exec("crontab -u $this->user $filename", $returnar, $return);
        if ($return != 0) {
            throw new \Exception("Error running crontab command as user $this->user ($return). Temporary file $filename not deleted.");
        } else {
            unlink($filename);
        }
    }

	/**
	 * Add a item of type CRON_CMD to the end of $this->crontabs
	 * @param string $m minute
	 * @param string $h hour
	 * @param string $dom day of month
	 * @param string $mo month
	 * @param string $dow day of week
	 * @param string $cmd command
     */
	function addCron($m, $h, $dom, $mo, $dow, $cmd)
    {
        $this->crontabs[] = [
            "minute"     => $m,
            "hour"       => $h,
            "dayofmonth" => $dom,
            "month"      => $mo,
            "dayofweek"  => $dow,
            "command"    => $cmd
        ];
        $this->linetypes[] = self::CRON_CMD;
    }

	/**
	 * Add a comment to the cron to the end of $this->crontabs
	 * @param string $comment
     */
	function addComment($comment)
    {
        $this->crontabs[] = "# $comment\n";
        $this->linetypes[] = self::CRON_COMMENT;
    }

	/**
	 * Add a special command (check man 5 crontab for more information)
	 * @param string $sdate
	 * @param string $cmd
     */
	function addSpecial($sdate, $cmd)
    {
        $this->crontabs[] = ["special" => $sdate, "command" => $cmd];
        $this->linetypes[] = self::CRON_SPECIAL;
    }

	/**
	 * Add an assignment (name = value)
	 * @param string $name
	 * @param string $value
     */
	function addAssign($name, $value)
    {
        $this->crontabs[] = ["name" => $name, "value" => $value];
        $this->linetypes[] = self::CRON_ASSIGN;
    }

	/**
	 * Delete a line from the arrays.
	 * @param int $index
     */
	function delEntry($index)
    {
        unset($this->crontabs[ $index ]);
        unset($this->linetypes[ $index ]);
    }

	/**
	 * Get all the lines of a certain type in an array
	 * @param int $type
	 *
	 * @return array|int
     */
    function getByType($type)
    {
        if ($type < self::CRON_COMMENT || $type > self::CRON_EMPTY) {
            trigger_error("Wrong type: $type", E_USER_WARNING);

            return 0;
        }

        $returnar = [];
        for ($i = 0; $i < count($this->linetypes); $i++) {
            if ($this->linetypes[ $i ] == $type) {
                $returnar[] = $this->crontabs[ $i ];
            }
        }

        return $returnar;
    }
}
