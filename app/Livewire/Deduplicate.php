<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Deduplicate extends Component
{
    protected $connection = 'mysql_mlsr';
    protected $table = 'buildings';

    public $isConfirmed = false;
    public $isPolling = false;
    public $fetching = false;
    public $status = ['Init'];
    public $batchSize = 200; // Number of duplicates to process per batch
    
    public $expectedUpdates = 0;
    public $expectedUniques = 0;
    public $updated = 0;
    public $uniques = 0;
    public $dbstats = [];

    public function mount(){
        $this->refreshUpdated();
    }

    public function refreshUpdated(){
        $connection = DB::connection($this->connection);
        $table = $this->table;
        /*
        $this->updated = $connection->table($this->table)->whereNotNull('deleted_at')->count();
        $this->uniques = $connection->table($this->table)->whereNull('deleted_at')->count();
        $this->expectedUniques = $connection->table($this->table)->distinct('slug')->count();
        $this->expectedUpdates = $connection->table($this->table)->select('id')->count() - $this->expectedUniques;
        // */ // single-query:
        $res = $connection->select("SELECT 
        (SELECT COUNT(`id`) FROM {$table}) AS `all_total`,
        (SELECT COUNT(DISTINCT IFNULL(`slug`,'is-null')) FROM {$table}) AS `unique_slugs`,
        (SELECT COUNT(id) - COUNT(DISTINCT IFNULL(`slug`,'is-null')) FROM {$table}) AS `to_delete`,
        (SELECT COUNT(id) FROM {$table} WHERE `deleted_at` IS NOT NULL ) AS `deleted`,
        (SELECT COUNT(id) FROM {$table} WHERE `deleted_at` IS NULL ) AS `total_existing`,
        'combined-results' as `desc` ");
        $this->dbstats = $res[0];
        $this->updated = $res[0]->deleted;
        $this->uniques = $res[0]->total_existing;
        $this->expectedUniques = $res[0]->unique_slugs;
        $this->expectedUpdates = $res[0]->to_delete;
    }

    public function togglePolling(){
        $this->isPolling = !$this->isPolling;
    }
    public function toggleConfirmed(){
        $this->isConfirmed = !$this->isConfirmed;
    }

    public function dispatchEvtCall(){
        $this->dispatch('statusUpdated', text:$this->status[count($this->status)-1]);
    }

    public function runDeduplication()
    {
        if(!$this->isConfirmed || !$this->isPolling){
            return;
        }
        $this->fetching = true;
        $table = $this->table;
        $connection = DB::connection($this->connection);
        $batchSize = $this->batchSize??200; 
        $now = Carbon::now();

        // $this->status[] = "Processing started";
        $this->dispatchEvtCall();  

        // do {
            $this->fetching=true;
            $_minCtSubQry = ' ROUND(LENGTH(GROUP_CONCAT(intid ORDER BY intid)) - LENGTH(REPLACE(GROUP_CONCAT(intid ORDER BY intid), MIN(intid), \'\'))) / LENGTH(MIN(intid)) '; 
            $_dupSlugs = $connection->table($table)->whereNull('deleted_at')
                ->groupBy('slug')
                ->selectRaw('`slug`, MIN(`intid`) AS `min_intid`, MAX(`import_id`) AS `max_import_id`, MAX(`updated`) AS `max_updated`, COUNT(`id`) AS `ct_dups`, '.$_minCtSubQry.' AS `min_intid_count` ')
                ->having('ct_dups', '>', 1)
                ->orderByDesc('ct_dups')
                ->limit($batchSize)
                ->get(['slug', 'min_intid', 'max_import_id', 'max_updated','min_intid_count'])
                ;

            // $this->status[]= $_dupSlugs->toSql() . ' | Bindings: '.implode(',', $_dupSlugs->getBindings());
                
            if($_dupSlugs->count()<=0){
                $this->status[] = "No more Duplicates Found! ";
                $this->isPolling = false;
                $this->dispatchEvtCall();
            }

            $this->status[]=implode(',',$_dupSlugs/*->get()*/->take(5)->pluck('slug')->toArray());

            foreach ($_dupSlugs as $_dupBld) {
                $op = $connection->table($table)->whereNull('deleted_at')
                ->where('slug', '=', $_dupBld->slug)
                ->where(function ($query) use ($_dupBld) {
                    $query->where('intid', '>', $_dupBld->min_intid);                    
                    // ->orWhere(function ($query2) use ($_dupBld) {$query2->havingRaw('`intid` = ? AND COUNT(*) > 1 AND `import_id` < ?', [$_dupBld->min_intid, $_dupBld->max_import_id ?? 0]);});
                    
                    if ($_dupBld->min_intid_count > 1) {
                        $query->orWhere(function ($query2) use ($_dupBld) {
                            $query2->where('intid', '=', $_dupBld->min_intid)
                            ->where('import_id', '<', $_dupBld->max_import_id ?? 0);
                        });
                    }

                })
                ->update(['deleted_at' => DB::raw('NOW()')]);
                
                // $this->status[]= /*$_minCtSubQry.'|--|'. */ round($_dupBld->min_intid_count) . ' -- ' . $op->toSql().'|Bindings: '.implode(', ',$op->getBindings());
                
                $this->status[] = "Processed slug: {$_dupBld->slug}";
                $this->dispatchEvtCall();  
                $this->fetching=false;
                // usleep(200000);
            }
            $this->refreshUpdated();
        // } while ($_dupSlugs->count() > 0);
        $this->fetching = false;

        // $this->status[] = 'Deduplication complete!';
        $this->dispatchEvtCall(); 
    }

    public function render()
    {
        return view('livewire.deduplicate');
    }
}
