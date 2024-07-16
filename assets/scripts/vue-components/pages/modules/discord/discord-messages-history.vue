<!-- Template -->
<template>
  <semipolar-spinner v-show="isSpinnerVisible"/>

  <div class="row">
    <div class="col-12 col-xl-12">
      <div class="card card-body bg-white border-light shadow-sm mb-4">
        <h2 class="h5 mb-4"> {{ sentDiscordMessagesLabel }} </h2>

        <information-text-for-block :displayed-text="informationTextTranslatedString"/>

        <section class="">
          <volt-table
              :headers="tableHeaders"
              :rows-data="allDataInTable"
              :search-able-properties="searchAbleProperties"
              @pagination-button-clicked="onPaginationButtonClickedHandler"
              @handle-showing-table-data-for-pagination-and-result="handleDataForPaginationAndSearch"
              @search-for-string-in-table-cells="searchForStringInTableCells"
              ref="table"
          >
            <volt-table-row
                v-for="(discordMessageDto, index) in currentlyVisibleDataInTable"
                :key="index"
                :row-data="discordMessageDto"
                :skipped-keys="skippedDtoProperties"
                @resend-clicked="onResendOptionClicked"
                @send-to-clicked="onSendToClicked"
            />
          </volt-table>
        </section>

      </div>
    </div>
  </div>

</template>

<!-- Script -->
<script>
import VoltTable                        from '../../../table/volt/table';
import VoltTableHead                    from '../../../table/volt/table-head';
import VoltTableBody                    from '../../../table/volt/table-body';
import VoltTableRow                     from '../../../table/volt/table-row';
import PreviewOption                    from '../../../other/actions/preview';
import SemipolarSpinnerComponent        from '../../../../vue-components/libs/epic-spinners/semipolar-spinner';
import InformationTextForBlockComponent from '../../../../vue-components/pages/components/information-text-for-block';

import Notification            from "../../../../libs/mdb5/Notification";
import TranslationsService     from "../../../../core/services/TranslationsService";
import SymfonyRoutes           from "../../../../core/symfony/SymfonyRoutes";

import DiscordMessageDto                from "../../../../core/dto/modules/discord/DiscordMessageDto";
import BaseInternalApiResponseDto       from '../../../../core/dto/api/internal/BaseInternalApiResponseDto';
import GetAllDiscordMessagesResponseDto from "../../../../core/dto/api/internal/GetAllDiscordMessagesResponseDto";

import ReSendOption        from '../../../other/actions/re-send';
import SendToOption        from '../../../other/actions/send-to';
import SendToDialogContent from './component/history/send-to-dialog-content';

let translationService = new TranslationsService();

export default {
  components: {
    "volt-table-body"            : VoltTableBody,
    "volt-table-head"            : VoltTableHead,
    "volt-table-row"             : VoltTableRow,
    "volt-table"                 : VoltTable,
    "semipolar-spinner"          : SemipolarSpinnerComponent,
    "information-text-for-block" : InformationTextForBlockComponent,
  },
  created(){
    this.retrieveAllDiscordWebhooks();
  },
  data(){
    return {
      allDiscordMessages          : [],
      currentlyVisibleDataInTable : [],
      allDataInTable              : [],
      isSpinnerVisible            : true,
    }
  },
  computed: {
    tableHeaders: {
      get: function () {
        return translationService.getTranslationsForStrings([
          'pages.discord.history.table.headers.messageTitle.label',
          'pages.discord.history.table.headers.status.label',
          'pages.discord.history.table.headers.created.label',
          'pages.discord.history.table.headers.options.label',
          'pages.discord.history.table.headers.info.label',
        ]);
      },
    },
    sentDiscordMessagesLabel: {
      get: function () {
        return translationService.getTranslationForString('pages.discord.history.labels.main');
      }
    },
    cachedAllDiscordMessages: {
      get: function() {
        return this.allDiscordMessages;
      },
      set: function (discordMessages) {
        this.allDiscordMessages = discordMessages;
      }
    },
    translationContentString: function(){
      return translationService.getTranslationForString('pages.discord.history.table.rows.tippyContent.contentString');
    },
    searchAbleProperties() {
      return [
        "messageContent",
        "messageTitle",
        "status",
      ];
    },
    skippedDtoProperties() {
      return [
        "isPlaceholderAssigned",
        "messageContent",
        "rawStatus",
        "id",
      ]
    },
    informationTextTranslatedString: function(){
      return translationService.getTranslationForString('pages.discord.history.texts.general');
    }
  },
  methods: {
    /**
     * @description retrieve all Discord Webhooks for further processing like for example display in table
     */
    retrieveAllDiscordWebhooks(){
      let notify = new Notification();

      this.axios.get(SymfonyRoutes.GET_ALL_DISCORD_MESSAGES).then( (response) => {
        let allDiscordWebhooksResponse = GetAllDiscordMessagesResponseDto.fromAxiosResponse(response);

        if (allDiscordWebhooksResponse.hasMessage()) {
          if (allDiscordWebhooksResponse.success) {
            notify.showGreenNotification(allDiscordWebhooksResponse.message);
          } else {
            notify.showRedNotification(allDiscordWebhooksResponse.message);
          }
        }

        let discordMessagesJsons = allDiscordWebhooksResponse.discordMessagesJsons;
        this.allDiscordMessages  = discordMessagesJsons.map( (json) => {
          return DiscordMessageDto.fromJson(json);
        });

        let tableDataDtos = discordMessagesJsons.map( (json) => {
          return DiscordMessageDto.fromJson(json);
        });

        this.currentlyVisibleDataInTable = this.processDiscordWebhooksDataForDisplayingInTable(tableDataDtos);
        this.allDataInTable              = this.processDiscordWebhooksDataForDisplayingInTable(tableDataDtos);
        this.isSpinnerVisible            = false;
      })
    },
    /**
     * @description build the content visible in the preview dialog
     *
     * @param discordMessageDto {DiscordMessageDto}
     */
    buildPreviewBodyContent(discordMessageDto){
      return `
          <b>${this.translationContentString}:</b>
          <br/>
          ${discordMessageDto.messageContent}
        `;
    },
    /**
     * @description will filter the data for displaying in table, either set empty value to skip them or add special
     *              formatting/styling
     *
     * @returns {Array<DiscordMessageDto>}
     */
    processDiscordWebhooksDataForDisplayingInTable(discordMessageDtos){
        let filteredTableData = [];

        discordMessageDtos.forEach( (discordMessage, index) => {

          let classes = "";
          switch(discordMessage.status){

            case DiscordMessageDto.STATUS_PENDING:
              classes+= " npl-text-color-dark-orange";
            break;

            case DiscordMessageDto.STATUS_SENT:
              classes+= " text-success";
            break;

            default:
              classes+= " text-danger";
            break;
          }

          // not using tippy as it's problematic here
         let infoPlaceholderUserMessage = translationService.getTranslationForString('pages.discord.history.table.headers.info.data.placeholderUser.text');

          discordMessage.status  = `<b class="${classes}">${discordMessage.status}</b>`;
          discordMessage.options = this.buildRowOptions(discordMessage);
          discordMessage.info    = "";

          if (discordMessage.isPlaceholderAssigned) {
            discordMessage.info += `<small title="${infoPlaceholderUserMessage}"><i class="fa fa-user-slash"></i></small>`
          }

          filteredTableData.push(discordMessage);
        });

        return filteredTableData;
    },
    /**
     * @description method triggered when the pagination button in table was clicked
     *
     * @param clickedPageNumber
     */
    onPaginationButtonClickedHandler(clickedPageNumber){
      let tableComponent               = this.$refs.table;
      tableComponent.currentResultPage = clickedPageNumber;
      tableComponent.handleShowingTableDataForPaginationAndResult(clickedPageNumber);
    },
    /**
     * @description method triggered when the table data is being filtered by the `pagination logic`
     *
     * @param shownResult
     */
    handleDataForPaginationAndSearch(shownResult){
      this.currentlyVisibleDataInTable = shownResult;
    },
    /**
     * @description method triggered when some data is changing in the search input for table
     *
     * @param searchResult
     */
    searchForStringInTableCells(searchResult){
      this.currentlyVisibleDataInTable = searchResult;
    },
    /**
     * @description will build the options for `options column` of single row
     *
     * @param discordMessage {DiscordMessageDto}
     */
    buildRowOptions(discordMessage) {

      let options = [];

      options.push({
         type      : "component",
         component : PreviewOption,
         props     : {
           content: this.buildPreviewBodyContent(discordMessage),
         }
      })

      if (
              discordMessage.isError()
          &&  !discordMessage.isPlaceholderAssigned
      ) {
        options.push({
         type      : "component",
         component : ReSendOption,
         props     : {
           content  : "Do You want to try to send this message again?",
           entityId : discordMessage.id
         }
       });
      }

      if (!discordMessage.isError()) {
        options.push({
         type:      "component",
         component: SendToOption,
         props:     {
           content: {
             component: SendToDialogContent,
             props    : {
               entityId : discordMessage.id
             }
           },
         }
       });
      }


      return options;
    },
    /**
     * @description handles re-sending the message
     * @param entityId {string}
     */
    onResendOptionClicked(entityId) {
      let calledUrl = SymfonyRoutes.buildUrlWithReplacedParams(SymfonyRoutes.RESEND_DISCORD_MESSAGE, {
        [SymfonyRoutes.RESEND_DISCORD_MESSAGE_PARAM_ID]: entityId,
      })

      this.isSpinnerVisible = true;
      this.axios.get(calledUrl).then( (response) => {
        let notification      = new Notification();
        this.isSpinnerVisible = false;
        let baseResponse = BaseInternalApiResponseDto.fromAxiosResponse(response);

        if (baseResponse.hasMessage()){
          if (baseResponse.success){
            notification.showGreenNotification(baseResponse.message);
            return;
          }

          notification.showRedNotification(baseResponse.message);
        }
      })
    },
    /**
     * @description will send email of given `id` to provided `email address`, doesn't modify the original email
     */
    onSendToClicked(data) {
      let calledUrl = SymfonyRoutes.buildUrlWithReplacedParams(SymfonyRoutes.SEND_DISCORD_MESSAGE_TO, {
        [SymfonyRoutes.SEND_DISCORD_MESSAGE_TO_PARAM_ID]: data.id
      })

      let dataBag = {
        webhookUrl: data.webhookUrl,
      };

      this.isSpinnerVisible = true;
      this.axios.post(calledUrl, dataBag).then( (response) => {
        let notification      = new Notification();
        this.isSpinnerVisible = false;
        let baseResponse = BaseInternalApiResponseDto.fromAxiosResponse(response);

        if (baseResponse.hasMessage()){
          if (baseResponse.success){
            notification.showGreenNotification(baseResponse.message);
            return;
          }

          notification.showRedNotification(baseResponse.message);
        }
      })
    }
  }
}
</script>