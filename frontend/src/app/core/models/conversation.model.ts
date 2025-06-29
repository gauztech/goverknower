import { BehaviorSubject, Observable } from 'rxjs';
import { Message } from './message.model';

export class Conversation {
    private messages: Message[];
    private msgSubject: BehaviorSubject<Message[]>;

    public msgObsever$: Observable<Message[]>;

    constructor(
        public id: string,
        public title: string = 'New Chat',
    ) {
        this.messages = [];
        this.msgSubject = new BehaviorSubject<Message[]>(this.messages);
        this.msgObsever$ = this.msgSubject.asObservable();
    }

    public addMessage(msg: string, sender: 'user' | 'ai') {
        this.messages.push(new Message(msg, sender));
        this.msgSubject.next([...this.messages]);
    }
}
