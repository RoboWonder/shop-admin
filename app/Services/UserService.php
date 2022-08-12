<?php
/**
 * Created by PhpStorm.
 * Date: 2022-08-03
 * Time: 22:17
 */

namespace App\Services;

use App\Constants\Message;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function doCreate(array $data)
    {
        try {
            $data['password'] = Hash::make($data['password']);

            $ok = $this->userRepository->create($data);
            if(!$ok){
                throw new \Exception(Message::ERR_SHOPBE_CREATE_FAIL);
            }
        }
        catch (\Exception $e){
            return $e;
        }

        return NULL;
    }

    public function getList(array $args): array
    {
        $rows = $paging = [];

        try {
            $model = $this->userRepository->getModel()->where(['deleted' => '0']);

            if (isset($args['filters']) && is_array($args['filters'])){
                $model = $model->where($args['filters']);
            }

            if (!!$args['page']){
                $page = (int)$args['page'];
                $size = (int)$args['size'];

                $paging = [
                    'page' => $page,
                    'size' => $size,
                    'total' => 0,
                    'last_page' => $page
                ];

                $data = $model->paginate($size, ['*'], 'page', $page);
                if(!!$data){
                    $paging = array_merge($paging, [
                        'total' => $data->total(),
                        'last_page' => $data->lastPage(),
                    ]);

                    $rows = $data->items();
                }
            } else {
                $rows = $model->all();
            }
        }
        catch (\Exception $e){
            return [NULL, $paging, $e];
        }

        return [$rows, $paging, NULL];
    }

    public function getView(string $id): array
    {
        try {
            $data = $this->userRepository->findWhere(['id' => $id, 'deleted' => '0']);
            if($data->isEmpty()){
                throw new \Exception(Message::ERR_SHOPBE_NO_DATA_FOUND);
            }
        }
        catch (\Exception $e){
            return [NULL, $e];
        }

        return [$data, NULL];
    }

    /***
     * only get one by conditions
     *
     * @param array $conditions
     *
     * @return array
     * @since: 2022/08/07 22:37
     */
    public function getViewBy(array $conditions): array
    {
        try {
            $data = $this->userRepository->findWhere(array_merge($conditions, ['deleted' => '0']));
            if($data->isEmpty()){
                throw new \Exception(Message::ERR_SHOPBE_NO_DATA_FOUND);
            }
            $data = $data->first();
        }
        catch (\Exception $e){
            return [NULL, $e];
        }

        return [$data, NULL];
    }

    /***
     * now was using for product order updated.
     *
     * @param int   $id
     * @param array $data
     *
     * @return \Exception|null
     * @since: 2022/08/07 22:16
     */
    public function doUpdate(int $id, array $data)
    {
        try {
            $ok = $this->userRepository->update($data, $id);
            if(!$ok){
                throw new \Exception(Message::ERR_SHOPBE_UPDATE_FAIL);
            }
        }
        catch (\Exception $e){
            return $e;
        }

        return NULL;
    }

    public function doDelete(string $id, bool $softDelete = TRUE)
    {
        try {
            if($softDelete){
                $ok = $this->userRepository->update(['deleted' => '1'], $id);
            }
            else{
                $ok = $this->userRepository->deleteWhere(['id' => $id]);
            }
            if(!$ok){
                throw new \Exception(Message::ERR_SHOPBE_DELETE_FAIL);
            }
        }
        catch (\Exception $e){
            return $e;
        }

        return NULL;
    }
}
