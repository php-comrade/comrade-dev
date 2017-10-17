import { Component, OnInit } from '@angular/core';
import {HttpService} from "./shared/http.service";
import {Router} from "@angular/router";
import {WampService} from "./shared/wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";
import {ServerError} from "./shared/server-error";
import {Job} from "./shared/job";
import {ToastyService} from "./shared/toasty.service";

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
      private wamp: WampService,
  ) {}

  ngOnInit() {
    if (!this.httpService.getApiBaseUrl()) {
      this.router.navigate(['settings', 'base-url']);
    }

    this.wamp.getWampBaseUrl().subscribe(() => {
      this.wamp.topic('job_manager.internal_error')
        .map((e: EventMessage) => e.args[0] as ServerError)
        .subscribe((error: ServerError) => {
          this.toastyService.serverError(error);
        })
      ;
      this.wamp.topic('comrade.job_updated')
        .map((e: EventMessage) => e.args[0] as Job)
        .subscribe((job: Job) => {
            this.toastyService.jobIsUpdated(job);
          }
        );
    });
  }
}
