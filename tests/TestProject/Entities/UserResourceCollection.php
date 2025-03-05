<?php
 
namespace AutoDoc\Laravel\Tests\TestProject\Entities;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
 
class UserResourceCollection extends ResourceCollection
{
    public static $wrap = 'users';
    
    public $collects = UserResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'users' => $this->collection,

            /**
             * Number of users returned.
             */
            'count' => $this->collection->count(),
        ];
    }
}
