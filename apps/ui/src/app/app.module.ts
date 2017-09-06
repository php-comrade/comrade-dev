import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { FormsModule } from "@angular/forms";
import { HttpModule }   from '@angular/http';

import { AppRoutingModule }     from './app-routing.module';
import { AppComponent }  from './app.component';
import { JobTemplateService } from "./shared/job-template.service";
import { JsonInputComponent } from './infra/json-input.component';
import { MomentModule } from "angular2-moment";
import { JobService } from "./shared/job.service";
import {NewCronTriggerComponent} from "./part/new-cron.trigger.component";
import {DatePickerModule} from "ng2-datepicker";
import {PrettycronPipe} from "./prettycron.pipe";
import {NewSimpleTriggerComponent} from "./part/new-simple.trigger.component";
import {TimelineService} from "./shared/timeline.service";
import {TimelineComponent} from "./page/timeline.component";
import {ListTriggersComponent} from "./part/list.triggers.component";
import { NavbarComponent } from './part/navbar.component';
import {StatusJobComponent} from "./part/status.job.component";
import {RunNowJobComponent} from "./part/run-now.job.component";
import {NewQueueRunnerComponent} from "./part/new-queue.runner.component";
import {ListRunnerComponent} from "./part/list.runner.component";
import {NewExclusivePolicyComponent} from "./part/new-exclusive.policy.component";
import {ShowExclusivePolicyComponent} from "./part/show-exclusive.policy.component";
import {NewGracePeriodPolicyComponent} from "./part/new-grace-period.policy.component";
import {ShowGracePeriodPolicyComponent} from "./part/show-grace-period.policy.component";
import {NewRetryFailedPolicyComponent} from "./part/new-retry-failed.policy.component";
import {ShowRetryFailedPolicyComponent} from "./part/show-retry-failed.policy.component";
import {NewRunSubJobsPolicyComponent} from "./part/new-run-sub-jobs.policy.component";
import {ShowRunSubJobsPolicyComponent} from "./part/show-run-sub-jobs.policy.component";
import {TemplateViewComponent} from "./page/template-view.component";
import {TemplateListComponent} from "./page/template-list.component";
import {TemplateNewComponent} from "./page/template-new.component";
import {JobViewComponent} from "./page/job-view.component";
import {TimeAgoComponent} from "./part/time-ago.component";
import {TimeCalComponent} from "./part/time-cal.component";
import {ProcessGraphImageComponent} from "./part/process-graph-image.component";
import {PrettyJsonModule} from 'angular2-prettyjson';
import {JobListComponent} from "./part/job-list.component";
import {JobListRowComponent} from "./part/job-list-row.component";
import {JobListHeaderRowComponent} from "./part/job-list-header-row.component";
import {ErrorService} from "./shared/error.service";
import {LateServerErrorsComponent} from "./page/late-server-errors.component";
import {HttpService} from "./shared/http.service";
import {ApiBaseUrlComponent} from "./page/api-base-url.component";
import {ApiGuard} from "./shared/api.guard";
import {Ng2Webstorage} from "ngx-webstorage";
import {WampService} from "./shared/wamp.service";


@NgModule({
  declarations: [
    AppComponent,
    TemplateListComponent,
    NavbarComponent,
    TemplateNewComponent,
    JsonInputComponent,
    TemplateViewComponent,
    NewCronTriggerComponent,
    NewSimpleTriggerComponent,
    ListTriggersComponent,
    TimelineComponent,
    PrettycronPipe,
    StatusJobComponent,
    RunNowJobComponent,
    NewQueueRunnerComponent,
    ListRunnerComponent,
    ShowExclusivePolicyComponent,
    NewExclusivePolicyComponent,
    NewGracePeriodPolicyComponent,
    ShowGracePeriodPolicyComponent,
    NewRetryFailedPolicyComponent,
    ShowRetryFailedPolicyComponent,
    NewRunSubJobsPolicyComponent,
    ShowRunSubJobsPolicyComponent,
    JobViewComponent,
    TimeAgoComponent,
    TimeCalComponent,
    ProcessGraphImageComponent,
    JobListComponent,
    JobListRowComponent,
    JobListHeaderRowComponent,
    LateServerErrorsComponent,
    ApiBaseUrlComponent,
  ],
  imports: [
    BrowserModule,
    FormsModule,
    AppRoutingModule,
    HttpModule,
    NgbModule.forRoot(),
    MomentModule,
    DatePickerModule,
    PrettyJsonModule,
    Ng2Webstorage,
  ],
  providers: [
      JobTemplateService,
      JobService,
      TimelineService,
      ErrorService,
      HttpService,
      ApiGuard,
      WampService,
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
