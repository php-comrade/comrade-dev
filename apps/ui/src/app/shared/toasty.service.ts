import {ToastOptions, ToastyConfig, ToastyService as OriginalToastyService} from "ng2-toasty";
import {Injectable} from "@angular/core";
import {ServerError} from "./server-error";
import {Job} from "./job";

@Injectable()
export class ToastyService {
  constructor(
    private toastyService: OriginalToastyService,
    private toastyConfig: ToastyConfig,
  ) {
    this.toastyConfig.theme = 'bootstrap';
  }

  serverError(error: ServerError): void {
    const toastOptions:ToastOptions = {title: "Server Error"};
    if (typeof error.error !== 'undefined' && typeof error.error.message !== 'undefined') {
      toastOptions.msg = `<a href="/errors/late">${error.error.message}</a>`;
    }

    this.toastyService.error(toastOptions);
  }

  jobIsUpdated(job: Job): void {
    if (job.currentResult.status == 'completed') {
      const toastOptions:ToastOptions = {
        title: "Job completed",
        msg: `<a href="/job/${job.id}/view">${job.name}</a>`
      };

      this.toastyService.success(toastOptions);
    } else if (job.currentResult.status == 'canceled' || job.currentResult.status == 'terminated') {
      const toastOptions:ToastOptions = {
        title: "Job canceled",
        msg: `<a href="/job/${job.id}/view">${job.name}</a>`
      };

      this.toastyService.warning(toastOptions);
    } else if (job.currentResult.status == 'failed') {
      const toastOptions:ToastOptions = {
        title: "Job failed",
        msg: `<a href="/job/${job.id}/view">${job.name}</a>`,
        timeout: 10000,
      };

      this.toastyService.error(toastOptions);
    }
  }

  apiBaseUrlForced(apiBaseUrl: string): void {
    const toastOptions:ToastOptions = {
      title: `API URL was forced`,
      msg: `The client uses "${apiBaseUrl}" endpoint now.`
    };

    this.toastyService.success(toastOptions);
  }
}