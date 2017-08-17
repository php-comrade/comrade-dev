import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { FormsModule } from "@angular/forms";
import { HttpModule }   from '@angular/http';

import { AppRoutingModule }     from './app-routing.module';
import { AppComponent }  from './app.component';
import { GridComponent } from "./template/grid.component";
import { JobTemplateService } from "./shared/job-template.service";
import { NavbarComponent } from './navbar/navbar.component';
import { NewComponent } from './template/new.component';
import { DetailsComponent } from './template/details.component';
import { JsonInputComponent } from './json-input/json-input.component';
import { MomentModule } from "angular2-moment";
import { JobService } from "./shared/job.service";
import { JobStatusComponent } from "./job/status.component";


@NgModule({
  declarations: [
    AppComponent,
    GridComponent,
    NavbarComponent,
    NewComponent,
    JsonInputComponent,
    DetailsComponent,
    JobStatusComponent,
  ],
  imports: [
    BrowserModule,
    FormsModule,
    AppRoutingModule,
    HttpModule,
    NgbModule.forRoot(),
    MomentModule
  ],
  providers: [
      JobTemplateService,
      JobService,
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
