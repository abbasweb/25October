<?php


namespace App\Repositories;


use App\Interfaces\MessageRepositoryInterface;
use App\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MessageRepository implements MessageRepositoryInterface
{
    private $model;
    public function __construct(Message $message)
    {
        $this->model = $message;
    }

    /**
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function index(int $id)
    {

        $messagesQuery  = $this->model->where(['user_id' => auth()->id() , 'to_user_id' => $id])->orWhere(function(Builder $query) use($id)
        {
           return $query->where(['user_id' => $id , 'to_user_id' => auth()->id()]);
        })->get();
        return $messagesQuery;
    }

    /**
     * @param array $data
     * @return Message|\Illuminate\Database\Eloquent\Model
     */
    public function store(array $data)
    {
        return $this->model->create([
           "to_user_id" => $data['receiverId'],
           "file" => $data['message_file'],
           "message" => $data['message'],
            "user_id" => auth()->id()
        ]);
    }

    /**
     * @param array $data
     * @param int $messageId
     * @return Message|Message[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function update(array $data, int $messageId)
    {
        $message = $this->findById($messageId);
        if (!$message)
            return null;
        if ($message->user_id != auth()->id())
            return null;
        if ($data['message_file'])
            Storage::delete("public/{$message->file}");
        $message->update([
            "message" => $data['message'],
            "file" => $data['message_file']
        ]);
        return $message;
    }

    /**
     * @param $id
     * @return Message|Message[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function findById($id)
    {
        return $this->model->find($id);
    }

    /**
     * @param int $messageId
     * @return Message|Message[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function see(int $messageId)
    {
        $message = $this->findById($messageId);
        $message->markAsSeen();
        return $message;
    }

    /**
     * @param int $messageId
     * @return Message|Message[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    public function destroy(int $messageId)
    {
        $message = $this->findById($messageId);
        if ($message->user_id != auth()->id())
            return null;
        if ($message->file)
            Storage::delete("public/{$message->file}");
        $message->delete();
        return $message;
    }

    /**
     * @return bool
     */
    public function destroyAllMessages()
    {
        $messages = $this->model->whereUserId(auth()->id())->get();
        if (!$messages) return false;
        $messages->each(function(Message $message)
        {
            $message->delete();
        });
        return true;
    }
}

