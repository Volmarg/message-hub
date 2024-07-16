/**
 * @description This class contains definitions of INTERNAL api routes defined on backend side
 *              there is no way to pass this via templates etc so whenever a route is being changed in the symfony
 *              it also has to be updated here.
 *
 *              This solution was added to avoid for example calling translation api and having string hardcoded in
 *              all the places.
 */
export default class SymfonyRoutes {

    /**
     * @description returns the data of currently logged in user
     */
    static readonly GET_LOGGED_IN_USER_DATA = "/api-internal/get-logged-in-user-data";

    /**
     * @description will send test email
     */
    static readonly SEND_TEST_MAIL = "/modules/mailing/send-test-mail";

    /**
     * @description returns the CSRF token - required by Symfony when the CSRF validation is turned on
     */
    static readonly GET_CSRF_TOKEN_PARAM_FORM_NAME = "{formName}";
    static readonly GET_CSRF_TOKEN                 = "/api-internal/get-csrf-token/{formName}";

    /**
     * @description returns all Emails entities data
     */
    static readonly GET_ALL_EMAILS = "/modules/mailing/get-all-emails";

    /**
     * @description returns all DiscordMessages entities data
     */
    static readonly GET_ALL_DISCORD_MESSAGES = "/modules/discord/get-all-discord-messages";

    /**
     * @description returns last processed emails
     *              used for example in the dashboard widget
     */
    static readonly GET_LAST_PROCESSED_EMAILS                    = "/modules/dashboard/get-last-processed-emails/{emailsCount}"
    static readonly GET_LAST_PROCESSED_EMAILS_PARAM_EMAILS_COUNT = "{emailsCount}";

    /**
     * @description returns last processed discord messages
     *              used for example in the dashboard widget
     */
    static readonly GET_LAST_PROCESSED_DISCORD_MESSAGES                      = "/modules/dashboard/get-last-processed-discord-messages/{messagesCount}"
    static readonly GET_LAST_PROCESSED_DISCORD_MESSAGES_PARAM_MESSAGES_COUNT = "{messagesCount}";

    /**
     * @description returns all defined webhooks used for sending messages in discord
     */
    static readonly GET_ALL_DISCORD_WEBHOOKS = "/modules/discord/get-all-webhooks";

    /**
     * @description will add single discord webhook
     */
    static readonly ADD_DISCORD_WEBHOOK = "/modules/discord/add-webhook";

    /**
     * @description will update single discord webhook
     */
    static readonly UPDATE_DISCORD_WEBHOOK = "/modules/discord/update-webhook";

    /**
     * @description handles the removal of single webhook by the provided id
     */
    static readonly REMOVE_DISCORD_WEBHOOK                  = "/modules/discord/remove-webhook/{webhookId}";
    static readonly REMOVE_DISCORD_WEBHOOK_PARAM_WEBHOOK_ID = "{webhookId}";

    /**
     * @description will send message to given webhook
     */
    static readonly SEND_DISCORD_TEST_MESSAGE = "/modules/discord/send-test-message-discord"

    /**
     * @description will add single discord webhook
     */
    static readonly ADD_MAIL_ACCOUNT = "/modules/mailing/add-mail-account";

    /**
     * @description will return all mail accounts
     */
    static readonly GET_ALL_MAIL_ACCOUNTS = "/modules/mailing/get-all-mails-accounts";

    /**
     * @description will return all supported mail clients
     */
    static readonly GET_SUPPORTED_MAIL_CLIENTS = "/modules/mailing/get-supported-mail-clients";

    /**
     * @description handles the removal of single account mail by the provided id
     */
    static readonly REMOVE_MAIL_ACCOUNT                       = "/modules/mailing/remove-mail-account/{mailAccountId}";
    static readonly REMOVE_MAIL_ACCOUNT_PARAM_MAIL_ACCOUNT_ID = "{mailAccountId}";

    /**
     * @description handles updating the entity in the backend
     */
    static readonly UPDATE_MAIL                  = '/modules/mailing/update-mail-account/{mailAccountId}';
    static readonly UPDATE_MAIL_PARAM_ACCOUNT_ID = '{mailAccountId}';

    /**
     * @description returns the information if system demo mode is on or not
     */
    static readonly ENV_IS_DEMO = "/api-internal/env/is-demo";

    /**
     * @description handles resending the email with given id
     */
    static readonly RESEND_EMAIL          = '/modules/mailing/re-send-email/{id}';
    static readonly RESEND_EMAIL_PARAM_ID = '{id}';

    /**
     * @description handles resending the email with given id
     */
    static readonly SEND_EMAIL_TO                     = '/modules/mailing/send-to/{id}/{emailAddress}';
    static readonly SEND_EMAIL_TO_PARAM_ID            = '{id}';
    static readonly SEND_EMAIL_TO_PARAM_EMAIL_ADDRESS = '{emailAddress}';

    /**
     * @description handles resending the email with given id
     */
    static readonly RESEND_DISCORD_MESSAGE          = '/modules/discord/re-send-message/{id}';
    static readonly RESEND_DISCORD_MESSAGE_PARAM_ID = '{id}';

    /**
     * @description handles resending the email with given id
     */
    static readonly SEND_DISCORD_MESSAGE_TO                           = '/modules/discord/send-to/{id}';
    static readonly SEND_DISCORD_MESSAGE_TO_PARAM_ID                  = '{id}';

    /**
     * @description will use the url with params and replace the params with values
     */
    public static buildUrlWithReplacedParams(processedUrl: string, replacedParamsWithValues: object): string
    {
        let keys = Object.keys(replacedParamsWithValues);
        keys.forEach( (key, index) => {
            let value = replacedParamsWithValues[key];
            processedUrl = processedUrl.replace(key, value);
        })

        return processedUrl;
    }

}