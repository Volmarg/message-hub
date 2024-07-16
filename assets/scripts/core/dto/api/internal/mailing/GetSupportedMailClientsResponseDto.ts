import BaseInternalApiResponseDto from "../BaseInternalApiResponseDto";

/**
 * @description Returns the dto representation from the backed response `getSupportedMailClients`
 */
export default class GetSupportedMailClientsResponseDto extends BaseInternalApiResponseDto
{
    private _clients: Array<string> = [];

    get clients(): Array<string> {
        return this._clients;
    }

    set clients(value: Array<string>) {
        this._clients = value;
    }

    /**
     * @description returns current dto as string
     */
    public toJson(): string
    {
        let object = {
            clients : this.clients,
            success : this.success,
            code    : this.code,
            message : this.message
        }

        return JSON.stringify(object);
    }

    /**
     * @description Create GetSupportedMailClientsResponseDto from json
     */
    public static fromJson(json: string): GetSupportedMailClientsResponseDto
    {
        let baseDto = super.fromJson(json);

        try{
            var object = JSON.parse(json);
        }catch(Exception){
            throw{
                "message"   : "Could not parse json to object for GetSupportedMailClientsResponseDto",
                "exception" : Exception
            }
        }

        let allEmailsResponseDto     = new GetSupportedMailClientsResponseDto();
        allEmailsResponseDto.success = baseDto.success;
        allEmailsResponseDto.code    = baseDto.code;
        allEmailsResponseDto.message = baseDto.message;
        allEmailsResponseDto.clients = object.clients;

        return allEmailsResponseDto;
    }

    /**
     * @description build GetSupportedMailClientsResponseDto from axios response object
     */
    public static fromAxiosResponse(axiosResponse: object): GetSupportedMailClientsResponseDto
    {
        let json = JSON.stringify(axiosResponse.data);
        let dto  = this.fromJson(json);

        return dto;
    }

}
