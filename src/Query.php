<?php
namespace FluentApi;

    /**
     * Class Query
     * support quick find
     * 1.equal
     * 2.not equal
     * 3.greater
     * 4.less
     * 5.or
     * 6.like
     * @package App\Uni\QueryUtils
     */
final class Query
{
    private $filters;
    private $orders;
    private $offset;
    private $limit;
    private $projection=[];
    protected $stack;

    public static function create() : self{
        return new self();
    }

    public function __construct()
    {
        $this->filters = [];
        $this->orders   = [];
        $this->offset  = 0;
        $this->limit   = 10;
        $this->stack = new Stack();
    }

    public function startOr() : self{
        $this->stack->push([
            '$or'=>[]
        ]);

        return $this;
    }

    public function closeOr(): self{
        $top = $this->stack->top();
        if (!isset($top['$or'])){
            throw new QueryException('close or always after start or');
        }
        // pop ra
        $or = $this->stack->pop();
        // stack trong, truong hop het end
        if ($this->stack->isEmpty()){
            $this->filters = array_merge($this->filters, $top);
            return $this;
        }
        // nhets vao cai ke tiep
        $top = $this->stack->top();
        $key = array_keys($top)[0];
        $top[$key][] = $or;
        //pop ra push vao. vi co the cai array kia no k phai ref
        $this->stack->pop();
        $this->stack->push($top);

        return $this;
    }

    public function startAnd() : self{
        $this->stack->push([
            '$and'=>[]
        ]);

        return $this;
    }

    private function putToStack($query){
        $top = $this->stack->top();
        $key = array_keys($top)[0];
        $top[$key][] = $query;
        //pop ra push vao. vi co the cai array kia no k phai ref
        $this->stack->pop();
        $this->stack->push($top);
        return $this;
    }


    public function closeAnd(): self{
        $top = $this->stack->top();
        if (!isset($top['$and'])){
            throw new QueryException('close and always after start and');
        }
        // pop ra
        $and = $this->stack->pop();
        // stack trong, truong hop het end
        if ($this->stack->isEmpty()){
            $this->filters = array_merge($this->filters, $top);
            return $this;
        }
        // nhets vao cai ke tiep
        $top = $this->stack->top();
        $key = array_keys($top)[0];
        $top[$key][] = $and;
        $this->stack->pop();
        $this->stack->push($top);
        return $this;
    }


    public function equal(string $field, $value) : Query{
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>$value]);
        }
        $this->filters = array_merge($this->filters, [$field=>$value]);
        return $this;
    }

    public function equalMulti($keyValues){
        if (!$this->stack->isEmpty()){
            return $this->putToStack($keyValues);
        }
        $this->filters = array_merge($this->filters, $keyValues);
        return $this;
    }

    public function notEqual(string $field, $value): Query{
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$ne'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$ne'=>$value]]);
        return $this;
    }

    public function checkExists(string $field, bool $value): Query
    {
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$exists'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$exists'=>$value]]);
        return $this;
    }

    public function greater(string $field, $value) : Query{
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$gt'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$gt'=>$value]]);
        return $this;
    }

    public function greaterEqual(string $field, $value) : Query{
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$gte'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$gte'=>$value]]);
        return $this;
    }

    public function lessEqual(string $field, $value): Query{
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$lte'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$lte'=>$value]]);
        return $this;
    }


    public function less(string $field, $value): Query{
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$lt'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$lt'=>$value]]);
        return $this;
    }

    public function in(string $field, array $value): Query
    {
        if (!$this->stack->isEmpty()){
            return $this->putToStack( [$field=>['$in'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$in'=>$value]]);
        return $this;
    }

    public function notIn(string $field, array $value): Query
    {
        if (!$this->stack->isEmpty()){
            return $this->putToStack([$field=>['$nin'=>$value]]);
        }
        $this->filters = array_merge($this->filters, [$field=>['$nin'=>$value]]);
        return $this;
    }

    public function orderBy(string $field, string $type = "ASC"): Query
    {
        if ($type=='ASC'){
            $this->orders = array_merge($this->orders, [$field=>1]);
        }else{
            $this->orders = array_merge($this->orders, [$field=>-1]);
        }

        return $this;
    }

    public function projects(...$keys): Query
    {
        $projects = [];
        foreach ($keys as $key){
            $projects[$key] = 1;
        }
        $this->projection = array_merge($this->projection, $projects);
        return $this;
    }


    public function limit(int $limit) : Query{
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset) : Query{
        $this->offset = $offset;
        return $this;
    }

    public function setPaging(Paging $page) :Query{
        $this->limit = $page->itemPerPages;
        $this->offset = $page->getOffset();
        return $this;
    }

    /**
     * @return array
     * @throws QueryException
     */
    public function getCondition(): array
    {
        if ($this->stack->isEmpty()){
            return $this->filters;
        }

        throw new QueryException('missing close api');
    }

    /**
     * @return array
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    public function toMongoQuery() : array{
        $options = array_merge($this->orders, ['limit'=>$this->limit]);
        $options = array_merge($options, ['offset'=>$this->offset]);
        $options = array_merge($options, ['projection'=>$this->projection]);

        return array($this->getCondition(), $options);
    }
}
