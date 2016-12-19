<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Log;

/**
 * Class Bw
 * @package App\Console\Commands
 */
class Bw extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bw:check {offset=0}{limit=10000000}';

    /**
     * @var \App\Http\Models\Bw Bw model.
     */
    private $bw;

    /**
     * Bw constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->bw = new \App\Http\Models\Bw();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        ini_set("memory_limit","2g");

        $this->info("[offset:".$this->argument('offset')."] Working sir! \n\n");

        $offset = intval($this->argument('offset'));
        $limit = intval($this->argument('limit'));
        $assets = $this->getAssets($offset,$limit);

        if(is_array($assets)===false || empty($assets)===true){
            $this->info('No assets to process.');
            return false;
        }

        $bar = $this->output->createProgressBar(count($assets));

        DB::beginTransaction();

        foreach ($assets as $asset) {
            $this->performTask($asset);
            $bar->advance();
        }

        DB::commit();

        $bar->finish();
        $this->info("\n\n Finished sir! \n\n");
    }

    /**
     * Select all assets from db (according to filter).
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    private function getAssets(int $offset, int $limit)
    {
        $query = '
            SELECT * 
            FROM assets a
            WHERE a.asset_deleted = 0
            AND a.asset_id < :id
            AND a.asset_classification_id IN (:class)
            OR a.asset_classification_id IS NULL
            ORDER BY a.asset_id asc
            LIMIT :offset,:limit
        ';

        //Anything under id X and where classification in X.
        return DB::select($query, ['id'=>471600,'class'=>'271,272','offset'=>$offset,'limit'=>$limit]);
    }

    /**
     * Perform the task
     * @param $asset
     * @return bool
     */
    private function performTask($asset)
    {
        $url = $this->generateAssetUrlPath($asset->asset_id);
        $bw = $this->bw->isBlackAndWhite($url);

        if(is_bool($bw)===false){
            return false;
        }

        $is_bw = ($bw === true ? 1 : 0);
        $is_colour = ($bw === false ? 1 : 0);

        /** Only update if different than actual */
        if($asset->asset_is_bnw !== $is_bw || $asset->asset_is_colour !== $is_colour){
            $update = '
              update assets 
              set asset_is_bnw = ?, asset_is_colour = ?
              where asset_id = ?
            ';
            $tags = [$is_bw,$is_colour,$asset->asset_id];
            $result = DB::update($update,$tags);
            if($result === 0){
                LOG::error( "[could not update]: ".  var_export($tags,true) );
            }
        }

        /** Just to make sure long process don't run out of memory. */
        gc_collect_cycles();
    }

    /**
     * Generate image path
     * @param int $id asset ID
     * @return string url
     */
    private function generateAssetUrlPath(int $id)
    {
        $template = "https://images-cdn.bridgemanimages.com/api/1.0/image/{size}.{supplierPrefix}.{calculatedId}.7055475/{id}.jpg";

        $calculatedId = strrev($id + 3179) . '0';

        $variables = [
            '{id}' => $id,
            '{calculatedId}' => $calculatedId,
            '{foreignMediaId}' => 666,
            '{supplierPrefix}' => 'XXX',
            '{size}' => 150,
        ];

        $url = str_replace(array_keys($variables), $variables, $template);
        return $url;
    }

}