<?php

namespace App\Console\Commands;

use App\Service\TelegramMessenger;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReadLogAndProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:read {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'read append only log file and show the processes content';

    /**
     * Execute the console command.
     */
    public function handle(TelegramMessenger $telegramMessenger)
    {
        $users = [];


        $file = $this->argument('path');
        if (!file_exists($file)) {
            $this->error("File is not found at {$file}");
            return;
        }
        $fileReader = new FileReader($file);
        $fileReader->seekToEndLine();
        while (true) {


            $newLine = $fileReader->readLine();

            if (empty($newLine)) {

                sleep(1);
                continue;
            }

            $d = explode(" ", trim($newLine));

            if (count($d) !== 7) {
                continue;
            }

            preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $d[2], $ip);
            $ip = $ip[0];
            $user = $d[6];


            if (!isset($users[$user])) {
                $users[$user] = [];
            }

            $addedNewIp = !isset($users[$user][$ip]);
            $users[$user][$ip] = Carbon::now();

            foreach ($users[$user] as $ip => $lastUsedAt) {
                /** @var Carbon $lastUsedAt */
                if ($lastUsedAt->isBefore(Carbon::now()->subMinutes(30))) {
                    unset($users[$user][$ip]);
                }
            }

            $shouldSend = $addedNewIp;
            $message = "User: {$user}";

            if ($addedNewIp) {
                $message .= "\nNew IP used";
            }
            if (count($users[$user]) > 3) {
                $message .= "\nUser reached 3 ip in 30 minute!";
                $shouldSend = true;
            }

            foreach ($users[$user] as $ip => $lastUsedAt) {
                $message .= "\nIP: {$ip} last used: {$lastUsedAt->diffForHumans()}";
            }


            $shouldSend = $shouldSend && $user == "Test User";
            if ($shouldSend)
                $telegramMessenger->sendMessage($message);

            $this->info("==================" . PHP_EOL . $message);

        }


        fclose($fp);

        return;
    }

}

class FileReader
{

    private $resource;
    private $readBuffer;

    public function __construct(private readonly string $path)
    {
        $this->readBuffer = "";
        $this->resource = fopen($this->path, 'r');
    }

    public function seekToEndLine(): void
    {
        $pos = -2;
        while (fgetc($this->resource) != "\n") {
            fseek($this->resource, $pos, SEEK_END);
            $pos = $pos - 1;
        }
    }

    public function readLine(): string
    {
        $i = 0;
        while (true) {

            $i++;
            $char = fread($this->resource, 1);


            if ($char === false || $char === "" || $char == "\n") {

                $tmp = $this->readBuffer;
                $this->readBuffer = "";
                return $tmp;
            }

            $this->readBuffer .= $char;


            if ($i > 1000)
                dd($char, $this->readBuffer);
        }

    }

}
