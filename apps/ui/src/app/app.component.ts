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
    if (!this.httpService.getApiBaseUrl()) {
      this.router.navigate(['settings', 'base-url']);
    }

    this.wamp.getWampBaseUrl().subscribe(() => {
      this.wamp.topic('job_manager.internal_error')
        .map((e: EventMessage) => e.args[0] as ServerError)
        .subscribe((error: ServerError) => {
          const toastOptions:ToastOptions = {title: "Server Error"};
          if (error.error.message !== undefined) {
            toastOptions.msg = `<a href="/errors/late">${error.error.message}</a>`;
          }

          this.toastyService.error(toastOptions);
        })
      ;
      this.wamp.topic('job_manager.update_job')
        .map((e: EventMessage) => e.args[0] as Job)
        .subscribe((job: Job) => {
            if (job.currentResult.status == 36 /** completed */) {
              const toastOptions:ToastOptions = {
                title: "Job completed",
                msg: `<a href="/job/${job.id}/view">${job.name}</a>`
              };

              this.toastyService.success(toastOptions);
            } else if (job.currentResult.status == 12 /** canceled */ || job.currentResult.status == 132 /** terminated */) {
              const toastOptions:ToastOptions = {
                title: "Job canceled",
                msg: `<a href="/job/${job.id}/view">${job.name}</a>`
              };

              this.toastyService.warning(toastOptions);
            } else if (job.currentResult.status == 68 /** failed */ ) {
              const toastOptions:ToastOptions = {
                title: "Job failed",
                msg: `<a href="/job/${job.id}/view">${job.name}</a>`,
                timeout: 10000,
              };

              this.toastyService.error(toastOptions);
            }
          }
        );
    });
  }
}
