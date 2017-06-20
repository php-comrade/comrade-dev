import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { FormsModule } from "@angular/forms";
import { HttpModule }   from '@angular/http';


import { AppRoutingModule }     from './app-routing.module';
import { AppComponent }  from './app.component';
import { GridComponent } from "./template/grid.component";
import {JobTemplateService} from "./shared/job-template.service";

@NgModule({
  declarations: [
    AppComponent,
    GridComponent
  ],
  imports: [
    BrowserModule,
    FormsModule,
    AppRoutingModule,
    HttpModule,
    NgbModule.forRoot()
  ],
  providers: [
      JobTemplateService,
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
