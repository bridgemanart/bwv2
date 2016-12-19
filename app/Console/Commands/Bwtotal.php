<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Log;

/**
 * Class Bw
 * @package App\Console\Commands
 */
class Bwtotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bw:total';


    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        ini_set("memory_limit","2g");

        $this->info("Working Sir! \n");

        $query = '
            SELECT count(*) total 
            FROM assets a
            WHERE a.asset_deleted = 0
            AND a.asset_id < :id
            AND a.asset_classification_id IN (:class)
            OR a.asset_classification_id IS NULL
            ORDER BY a.asset_id asc
            LIMIT 0,99999999999999
        ';

        $total = DB::select($query, ['id'=>471600,'class'=>'271,272']);
        $this->info("[total: ".var_export( (isset($total[0]->total)===true ? $total[0]->total : '???'),true )."] \n");
        $this->info("Finished sir! \n\n");
    }
}