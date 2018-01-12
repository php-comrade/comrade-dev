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
import {JobStatusComponent} from "./part/job-status.component";
import {RunNowJobComponent} from "./part/run-now.job.component";
import {RunnerNewQueueComponent} from "./part/runner-new-queue.component";
import {RunnerNewHttpComponent} from "./part/runner-new-http.component";
import {RunnerListComponent} from "./part/list.runner.component";
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
import {JobFlowGraphComponent} from "./part/job-flow-graph.component";
import {PrettyJsonModule} from 'angular2-prettyjson';
import {JobListComponent} from "./part/job-list.component";
import {JobListRowComponent} from "./part/job-list-row.component";
import {JobListHeaderRowComponent} from "./part/job-list-header-row.component";
import {ErrorService} from "./shared/error.service";
import {LateServerErrorsComponent} from "./page/late-server-errors.component";
import {HttpService} from "./shared/http.service";
import {ApiBaseUrlComponent} from "./page/api-base-url.component";
import {Ng2Webstorage} from "ngx-webstorage";
import {WampService} from "./shared/wamp.service";
import {CurrentJobService} from "./shared/current-job.service";
import {ToastyModule} from "ng2-toasty";
import {CurrentJobTemplateService} from "./shared/current-job-template.service";
import {LandingComponent} from "./page/landing.component";
import {TemplateViewChartComponent} from "./part/template-view-chart.component";
import {ChartsModule} from "ng2-charts";
import {ToastyService} from "./shared/toasty.service";
import {CurrentSubJobsService} from "./shared/current-sub-jobs.service";
import {PolicyNewSubJobComponent} from "./part/policy-new-sub-job.component";
import {PolicyShowSubJobComponent} from "./part/policy-show-sub-job.component";
import {JobStateGraphComponent} from "./part/job-state-graph.component";
import {JobExecutionTabComponent} from "./part/job-execution-tab.component";
import {TemplateTriggersComponent} from "./part/template-triggers.component";
import {PolicyNewRunDependentJobComponent} from "./part/policy-new-dependent-job.component";
import {Ng2CompleterModule} from "ng2-completer";
import {TemplateSearchComponent} from "./part/template-search.component";
import {PolicyShowRunDependentJobComponent} from "./part/policy-show-run-dependent-job.component";
import {JobDependentFlowGraphComponent} from "./part/job-dependent-flow-graph.component";
import {WampGuard} from "./shared/wamp.guard";

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
    JobStatusComponent,
    RunNowJobComponent,
    RunnerNewQueueComponent,
    RunnerNewHttpComponent,
    RunnerListComponent,
    ShowExclusivePolicyComponent,
    NewExclusivePolicyComponent,
    NewGracePeriodPolicyComponent,
    ShowGracePeriodPolicyComponent,
    NewRetryFailedPolicyComponent,
    ShowRetryFailedPolicyComponent,
    NewRunSubJobsPolicyComponent,
    ShowRunSubJobsPolicyComponent,
    TimeAgoComponent,
    TimeCalComponent,
    JobFlowGraphComponent,
    JobDependentFlowGraphComponent,
    JobStateGraphComponent,
    JobViewComponent,
    JobExecutionTabComponent,
    JobListComponent,
    JobListRowComponent,
    JobListHeaderRowComponent,
    LateServerErrorsComponent,
    ApiBaseUrlComponent,
    LandingComponent,
    TemplateViewChartComponent,
    TemplateTriggersComponent,
    TemplateSearchComponent,
    PolicyNewSubJobComponent,
    PolicyNewRunDependentJobComponent,
    PolicyShowSubJobComponent,
    PolicyShowRunDependentJobComponent,
  ],
  imports: [
    BrowserModule,
    FormsModule,
    Ng2CompleterModule,
    AppRoutingModule,
    HttpModule,
    NgbModule.forRoot(),
    MomentModule,
    DatePickerModule,
    PrettyJsonModule,
    Ng2Webstorage,
    ToastyModule.forRoot(),
    ChartsModule,
  ],
  providers: [
      JobTemplateService,
      JobService,
      TimelineService,
      ErrorService,
      HttpService,
      WampGuard,
      WampService,
      CurrentJobService,
      CurrentJobTemplateService,
      CurrentSubJobsService,
      ToastyService
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
