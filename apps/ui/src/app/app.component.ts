import { Component, OnInit } from '@angular/core';
import {HttpService} from "./shared/http.service";
import {Router} from "@angular/router";
import {ToastyService, ToastyConfig, ToastOptions} from "ng2-toasty";
import {WampService} from "./shared/wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";
import {ServerError} from "./shared/server-error";
import {Job} from "./shared/job";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  title = 'Comrade UI';

  constructor(
      private httpService: HttpService,
      private router: Router,
      private toastyService: ToastyService,
      private toastyConfig: ToastyConfig,
      private wamp: WampService,
  ) {
      this.toastyConfig.theme = 'bootstrap';
  }

  ngOnInit() {
      this.wamp.topic('job_manager.internal_error')
          .map((e: EventMessage) => e.args[0] as ServerError)
          .subscribe((error: ServerError) => {
              const toastOptions:ToastOptions = {
                title: "Server Error",
                msg: `<a href="/errors/late">${error.error.message}</a>`
              };

              this.toastyService.error(toastOptions);
          })
      ;
      this.wamp.topic('job_manager.update_job')
          .map((e: EventMessage) => e.args[0] as Job)
          .subscribe((job: Job) => {
              const toastOptions:ToastOptions = {
                  title: "Job updated",
                  msg: `<a href="/job/${job.id}/view">${job.name}</a>`
              };

              if (job.currentResult.status == 68 /** failed */ ) {
                  this.toastyService.error(toastOptions);
              } else {
                  this.toastyService.info(toastOptions);
              }
          }
      )


    if (!this.httpService.getApiBaseUrl()) {
      this.router.navigate(['settings', 'base-url']);
    }
  }
}
