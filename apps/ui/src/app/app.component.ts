import { Component, OnInit } from '@angular/core';
import {HttpService} from "./shared/http.service";
import {Router} from "@angular/router";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  title = 'Comrade UI';

  constructor(private httpService: HttpService, private router: Router) {}

  ngOnInit() {
    if (!this.httpService.getApiBaseUrl()) {
      this.router.navigate(['settings', 'base-url']);
    }
  }
}
