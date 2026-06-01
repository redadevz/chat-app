

export type Message = {
    id: string | number;
    conversation_id: string | number;
    user_id: string | number;
    reply_to_id: string | number;
    body: string;
    type: string;
    
};

export type MessageForm = {
    conversation_id: string | number;
    user_id: string | number;
    reply_to_id: string | number;
    body: string;
    type: string;
};
