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
import {JobDetailsComponent} from "./page/job-details.component";
import {JobGridComponent} from "./page/job-grid.component";
import {JobNewComponent} from "./page/job-new.component";
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


@NgModule({
  declarations: [
    AppComponent,
    JobGridComponent,
    NavbarComponent,
    JobNewComponent,
    JsonInputComponent,
    JobDetailsComponent,
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
  ],
  imports: [
    BrowserModule,
    FormsModule,
    AppRoutingModule,
    HttpModule,
    NgbModule.forRoot(),
    MomentModule,
    DatePickerModule,
  ],
  providers: [
      JobTemplateService,
      JobService,
      TimelineService,
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
