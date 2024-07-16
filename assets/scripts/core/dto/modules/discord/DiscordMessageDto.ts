/**
 * @description returns dto representation of backend dto which consist of DiscordMessage Entity data.
 */
export default class DiscordMessageDto
{
    static readonly STATUS_SENT    = "SENT";
    static readonly STATUS_PENDING = "PENDING";
    static readonly STATUS_ERROR   = "ERROR";

    private _id                    : number;
    private _webhookName           : string = "";
    private _messageContent        : string = "";
    private _source                : string = "";
    private _messageTitle          : string = "";
    private _status                : string = "";
    private _rawStatus             : string = "";
    private _created               : string = "";
    private _isPlaceholderAssigned : boolean = false;

    get id(): number {
        return this._id;
    }

    set id(value: number) {
        this._id = value;
    }

    get webhookName(): string {
        return this._webhookName;
    }

    set webhookName(value: string) {
        this._webhookName = value;
    }

    get messageContent(): string {
        return this._messageContent;
    }

    set messageContent(value: string) {
        this._messageContent = value;
    }

    get messageTitle(): string {
        return this._messageTitle;
    }

    set messageTitle(value: string) {
        this._messageTitle = value;
    }

    get source(): string {
        return this._source;
    }

    set source(value: string) {
        this._source = value;
    }

    get status(): string {
        return this._status;
    }

    set status(value: string) {
        this._status = value;
    }

    get created(): string {
        return this._created;
    }

    set created(value: string) {
        this._created = value;
    }

    get rawStatus(): string {
        return this._rawStatus;
    }

    set rawStatus(value: string) {
        this._rawStatus = value;
    }

    get isPlaceholderAssigned(): boolean {
        return this._isPlaceholderAssigned;
    }

    set isPlaceholderAssigned(value: boolean) {
        this._isPlaceholderAssigned = value;
    }

    /**
     * @description Check if the sending status is error
     */
    public isError(): boolean
    {
        return this.rawStatus === DiscordMessageDto.STATUS_ERROR;
    }

    /**
     * Will produce dto from json
     *
     * @param json
     */
    public static fromJson(json: string): DiscordMessageDto
    {
        let object = JSON.parse(json);
        let dto    = new DiscordMessageDto();

        dto._id                    = object.id;
        dto._webhookName           = object.webhookName;
        dto._messageContent        = object.messageContent;
        dto._messageTitle          = object.messageTitle;
        dto._source                = object.source;
        dto._status                = object.status;
        dto._rawStatus             = object.status;
        dto._created               = object.created;
        dto._isPlaceholderAssigned = object.isPlaceholderAssigned;

        return dto;
    }

}