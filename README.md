# MongoFluentApi
## Mongo fluent api for query

```php
$query = new Query;
$query->equal('name','david')->startAnd()->equal('address','NY')
->startOr()->less('age',14)->greater('age',22)->closeOr()->closeAnd();
list($condition, $option) = $query->toMongoQuery();
$mongo->find($condition, $option);
```
