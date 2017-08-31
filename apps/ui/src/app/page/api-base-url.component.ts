import { Component, OnInit } from '@angular/core';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {HttpService} from "../shared/http.service";
import {Observable} from "rxjs/Observable";
import 'rxjs/add/observable/of';
import {Router} from "@angular/router";

@Component({
  selector: 'api-base-url',
  template: `
      <div *ngIf="error" class="alert alert-danger" role="alert">{{ error }}</div>

          <div class="form-group row">
              <div class="col-md-2">
                  <p class="form-control-static">Api base url:</p>
              </div>
              <div class="col-md-5">
                  <input
                      class="form-control "
                      id="api-base-url"
                      type="url"
                      [ngClass]="{'ng-valid': testResult === true, 'ng-invalid': testResult === false}"
                      required
                      (input)="resetResult()"
                      (focus)="resetResult()"
                      [value]="apiBaseUrl" #baseUrl
                  >
              </div>
              <div class="col-md-1">
                  <button *ngIf="!testResult" class="btn btn-default btn-success" [ngClass]="{'btn-success': testResult === true, 'btn-danger': testResult === false}" (click)="testBaseUrl(baseUrl.value)">Test</button>
                  <button *ngIf="testResult" class="btn btn-default btn-success" (click)="useBaseUrl(baseUrl.value)">Use</button>
              </div>
          </div>
  `,
})
export class ApiBaseUrlComponent implements OnInit {
    private apiBaseUrl: string;

    private testResult: boolean;

    constructor(private httpService: HttpService, private router: Router) {}

    ngOnInit() {
        this.apiBaseUrl = this.httpService.getApiBaseUrl();
        this.testBaseUrl(this.apiBaseUrl);
    }

    resetResult() {
        this.testResult = null;
    }

    testBaseUrl(apiBaseUrl: string):void {
        if (!apiBaseUrl) {
            this.testResult = null;

            return;
        }

        this.httpService.getInfo(apiBaseUrl)
            .catch(err => Observable.throw(err))
            .subscribe(
                res => this.testResult = true,
                err => this.testResult = false,
            )
        ;
    }

    useBaseUrl(apiBaseUrl: string) {
        this.httpService.changeApiBaseUrl(apiBaseUrl);

        this.router.navigate(['']);
    }
}
