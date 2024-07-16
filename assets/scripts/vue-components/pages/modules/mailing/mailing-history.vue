<!-- Template -->
<template>
  <semipolar-spinner v-show="isSpinnerVisible"/>

  <div class="row">
    <div class="col-12 col-xl-12">
      <div class="card card-body bg-white border-light shadow-sm mb-4">
        <h2 class="h5 mb-4"> {{ sentEmailsLabel }}  </h2>

        <information-text-for-block :displayed-text="informationTextTranslatedString"/>

        <section class="mailing-history-table-wrapper">

          <volt-table
              :headers="tableHeaders"
              :rows-data="tableData"
              :search-able-properties="searchAbleProperties"
              @pagination-button-clicked="onPaginationButtonClickedHandler"
              @handle-showing-table-data-for-pagination-and-result="handleDataForPaginationAndSearch"
              @search-for-string-in-table-cells="searchForStringInTableCells"
              ref="table"
          >
            <volt-table-row
                v-for="(mailDto, index) in currentlyVisibleDataInTable"
                :key="index"
                :row-data="mailDto"
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
import SemipolarSpinnerComponent        from '../../../../vue-components/libs/epic-spinners/semipolar-spinner';
import InformationTextForBlockComponent from '../../../../vue-components/pages/components/information-text-for-block';
import PreviewOption                    from "../../../other/actions/preview";
import ReSendOption                     from "../../../other/actions/re-send";
import SendToOption                     from "../../../other/actions/send-to";
import SendToDialogContent              from "./component/history/send-to-dialog-content";

import TranslationsService     from "../../../../core/services/TranslationsService";
import SymfonyRoutes           from "../../../../core/symfony/SymfonyRoutes";
import GetAllEmailsResponseDto from "../../../../core/dto/api/internal/GetAllEmailsResponseDto";
import MailDto                 from "../../../../core/dto/modules/mailing/MailDto";
import BaseInternalApiResponseDto from '../../../../core/dto/api/internal/BaseInternalApiResponseDto';
import Notification from '../../../../libs/mdb5/Notification';

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
    this.retrieveAllEmails();
  },
  data(){
    return {
      allEmails                   : [],
      tableData                   : [],
      currentlyVisibleDataInTable : [],
      isSpinnerVisible            : true,
    }
  },
  computed: {
    tableHeaders: {
      get: function () {
        return translationService.getTranslationsForStrings([
          'pages.mailing.history.table.headers.subject.label',
          'pages.mailing.history.table.headers.status.label',
          'pages.mailing.history.table.headers.created.label',
          'pages.mailing.history.table.headers.receivers.label',
          'pages.mailing.history.table.headers.options.label'
        ]);
      },
    },
    sentEmailsLabel: {
      get: function () {
        return translationService.getTranslationForString('pages.mailing.history.labels.main');
      }
    },
    allMails: {
      get: function() {
        return this.allEmails;
      },
      set: function (emails) {
        this.allEmails = emails;
      }
    },
    searchAbleProperties() {
      return [
        "status",
        "subject",
        "toEmails",
      ];
    },
    skippedDtoProperties(){
      return [
        "id",
        "body",
        "subject",
        "rawStatus",
      ];
    },
    informationTextTranslatedString: function(){
      return translationService.getTranslationForString('pages.mailing.history.texts.general');
    }
  },
  methods: {
    /**
     * @description retrieve all Mails for further processing like for example display in table
     */
    retrieveAllEmails(){
      this.axios.get(SymfonyRoutes.GET_ALL_EMAILS).then( (response) => {
        let allEmailsResponseDtos = GetAllEmailsResponseDto.fromAxiosResponse(response);
        let emailsJsons           = allEmailsResponseDtos.emailsJsons;

        this.allMails = emailsJsons.map( (json) => {
          return MailDto.fromJson(json);
        });

        let tableDataDtos = emailsJsons.map( (json) => {
          return MailDto.fromJson(json);
        });

        this.currentlyVisibleDataInTable = this.processMailsDataForDisplayingInTable(tableDataDtos);
        this.tableData                   = this.processMailsDataForDisplayingInTable(tableDataDtos);
        this.isSpinnerVisible            = false;
      })
    },
    /**
     * @description will filter the data for displaying in table, either set empty value to skip them or add special
     *              formatting/styling
     *
     * @param mailsDtos {Array<MailDto>}
     * @returns {Array<MailDto>}
     */
    processMailsDataForDisplayingInTable(mailsDtos){
        let filteredTableData = [];
        let maxSubjectLength = 75;

        mailsDtos.forEach( (mail, index) => {

          let classes = "";
          switch(mail.status){

            case MailDto.STATUS_PENDING:
              classes+= " npl-text-color-dark-orange";
            break;

            case MailDto.STATUS_SENT:
              classes+= " text-success";
            break;

            default:
              classes+= " text-danger";
            break;
          }

          mail.status = `<b class="${classes}">${mail.status}</b>`;
          mail.options = this.buildRowOptions(mail);

          mail.shortSubject = mail.subject;
          if (mail.subject.length > maxSubjectLength) {
            mail.shortSubject = mail.shortSubject.substr(0, maxSubjectLength) + "...";
          }

          filteredTableData.push(mail);
        })

        return filteredTableData;
      },
    /**
     * @description will build the options for `options column` of single row
     *
     * @param mail {MailDto}
     */
      buildRowOptions(mail) {
        let options = [];

        options.push({
           type      : "component",
           component : PreviewOption,
           props     : {
             content:                      mail.body,
             subject:                      mail.subject,
             dialogSize:                   "full",
             useIframe:                    true,
             showDialogCancelButton:       true,
             showDialogConfirmationButton: false
           }
         });

        if (mail.isError()) {
          options.push({
            type      : "component",
            component : ReSendOption,
            props     : {
              content  : "Do You want to try to send this message again?",
              entityId : mail.id
            }
          });
        }

        if (!mail.isError()) {
          options.push({
            type:      "component",
            component: SendToOption,
            props:     {
              content: {
                component: SendToDialogContent,
                props    : {
                  entityId : mail.id
                }
              },
            }
          });
        }

        return options;
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
      * @description handles re-sending the message
      * @param entityId {string}
      */
      onResendOptionClicked(entityId) {
        let calledUrl = SymfonyRoutes.buildUrlWithReplacedParams(SymfonyRoutes.RESEND_EMAIL, {
          [SymfonyRoutes.RESEND_EMAIL_PARAM_ID]: entityId,
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

        let calledUrl = SymfonyRoutes.buildUrlWithReplacedParams(SymfonyRoutes.SEND_EMAIL_TO, {
          [SymfonyRoutes.SEND_EMAIL_TO_PARAM_ID]            : data.id,
          [SymfonyRoutes.SEND_EMAIL_TO_PARAM_EMAIL_ADDRESS] : data.emailAddress,
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

      }
  }
}
</script>