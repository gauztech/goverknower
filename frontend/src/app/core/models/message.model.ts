import * as uuid from 'uuid';

export class Message {
    public id: string;

    constructor(
        public text: string,
        public sender: 'user' | 'ai',
    ) {
        this.id = uuid.v4();
    }
}
