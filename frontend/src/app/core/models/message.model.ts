export class Message {
    constructor(
        public text: string,
        public sender: 'user' | 'ai',
    ) {}
}
